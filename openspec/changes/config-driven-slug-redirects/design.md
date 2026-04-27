## Context

Today's "automatic redirect on slug change" works through two `AbstractTypeExtension`s on `ProductTranslationType` / `TaxonTranslationType`. The extensions add a `mapped: false` checkbox via `PRE_SET_DATA`, snapshot the old slug into an in-memory `array<string,string>` keyed by `spl_object_hash`, and on `POST_SUBMIT` call a `SlugUpdateHandler` if the box was checked and the slug changed. URL generation is encoded in two abstract subclasses (`ProductTranslationSlugUpdateHandler::generateUrl()`, `TaxonTranslationSlugUpdateHandler::generateUrl()`).

This setup has three friction points:

1. The trigger is hard-coded to two specific form types. Adding a third resource means a new form-type extension and a new handler subclass.
2. The decision to create a redirect lives per-save rather than per-deployment — ops can't say "this store always wants redirects on product slug changes."
3. The plumbing requires two templates, two twig hooks, and a translation key whose only purpose is rendering the checkbox.

The new architecture moves the trigger to the Sylius `ResourceController` event stream and the configuration to the plugin tree. URL generation moves to a composite resolver registered through `setono/composite-compiler-pass`.

Stakeholders: plugin maintainers (this repo), Sylius store operators (config consumers), plugin extenders (anyone adding redirect coverage for additional `SlugAware` resources).

## Goals / Non-Goals

**Goals:**

- Decouple the trigger from form types: any admin update that flows through `ResourceController::updateAction` of a configured resource produces a redirect.
- Surface the opt-in as plugin configuration, keyed by Sylius resource alias.
- Validate misconfiguration loudly at compile time (unknown alias, alias whose model is not `SlugAware`, alias with no resolver in the composite).
- Provide a clean extension point (`AutomaticRedirectUrlResolverInterface`) so third parties can add coverage for their own slug-aware resources without subclassing the handler.
- Keep redirect creation defaults narrow and intentional: `permanent = true`, `only404 = true`, `channels = []`.

**Non-Goals:**

- Triggering redirects from non-admin code paths (API, CLI, fixtures, custom controllers, Doctrine listeners). The change deliberately scopes itself to the Sylius `ResourceController`.
- Configurability of redirect attributes (`permanent`, `only404`, channel scoping) per resource. Defaults are hardcoded; users wanting different attributes use manual redirects.
- Bulk slug-change tooling, async/queued redirect creation, or migration of pre-existing manual redirects.
- Any change to `RequestSubscriber`, `NotFoundSubscriber`, or the existing manual-redirect admin flow.

## Decisions

### Trigger: Sylius `ResourceController` events, not Doctrine lifecycle events

The exploration weighed Doctrine `onFlush` (catches every code path) against Sylius resource events (catches only admin/API CRUD via the resource controller). We chose the Sylius events because the user asked for an admin-only feature, and because that constraint also dissolves two secondary problems:

- **Error policy.** `pre_update` runs inside the controller's request, so a `SlugUpdateHandlerValidationException` can bubble out and the controller's existing failure UX takes over (flash + form re-render). No "swallow vs. bubble" dilemma.
- **No spurious triggers** during fixture loading, data migrations, or repository-level writes. Those paths don't dispatch resource events.

A reality check on event names: Sylius's `ResourceController::updateAction` dispatches exactly two events — `<alias>.pre_update` (fired *after* the form has bound the new data into the resource but *before* the `ResourceUpdateHandler` flushes) and `<alias>.post_update` (after flush). There is no `<alias>.initialize_update`. The plugin therefore acts on `pre_update` only.

Alternatives considered: Doctrine `onFlush` (rejected — too broad given the user's "admin only" constraint), keeping the form extension with a hidden checkbox (rejected — no real architectural improvement), `kernel.controller` interception (rejected — fires too broadly).

### Configuration shape: per-alias boolean keyed by Sylius resource alias

```yaml
setono_sylius_redirect:
    automatic_redirects:
        sylius.product: true
        sylius.taxon:   true
```

Decisions inside this shape:

- **Keys are Sylius resource aliases.** The plugin already resolves resources through Sylius's registry; using the registry's own naming keeps user mental models aligned. Class FQCNs were considered but rejected because Sylius itself prefers aliases everywhere else.
- **Values are bare booleans, not arrays.** A future need for per-resource options (e.g. opting out of `only404`) can migrate to an associative array shape via a config-tree variable node without breaking the boolean form. We hold that option in reserve and start with the simpler shape.
- **Default is "absent means false".** Listing nothing means the feature is off everywhere. There is no "global enable" switch; each alias must be explicitly listed.
- **Compile-time validation.** During `Extension::load`, every configured alias is fetched from `Sylius\Resource\Metadata\RegistryInterface`. Unknown alias → `InvalidConfigurationException`. Model that does not implement `SlugAwareInterface` → `InvalidConfigurationException`. This catches typos like `sylius.products` at container build time.

Alternatives considered: a single boolean `automatic_redirects: true` (too coarse — products and taxons rename at very different cadences); a fully-nested array per alias (premature, no second knob to put under it yet).

### Extension point: `AutomaticRedirectUrlResolverInterface` composite via `setono/composite-compiler-pass`

```php
interface AutomaticRedirectUrlResolverInterface
{
    public function supports(string $class): bool;

    public function resolve(object $resource, string $slug, string $locale): string;
}
```

A composite collects all tagged resolvers; on each `resolve()` call it walks the children, returns the first whose `supports()` matches, throws if none match. The composite itself implements the same interface, so the rest of the codebase only sees `AutomaticRedirectUrlResolverInterface`.

`supports()` discriminates by class string, not instance, so the composite can be queried during compile-time validation without instantiating resources. The plugin ships two resolvers (`ProductAutomaticRedirectUrlResolver`, `TaxonAutomaticRedirectUrlResolver`) tagged with `setono_sylius_redirect.automatic_redirect_url_resolver`. The compiler pass is the standard one from `setono/composite-compiler-pass`, configured against that tag.

Compile-time validation hooks here too: every alias listed in the config tree must (a) be known to Sylius's resource registry, (b) resolve to a class implementing `SlugAwareInterface`, and (c) be `supports()`-able by at least one tagged resolver. (c) requires resolving services during the compiler pass — feasible since `supports()` only inspects a class string. Failing (c) raises a `RuntimeException` at container build.

Alternatives considered: a registry-style map keyed by alias (rejected — couples the resolver to the alias rather than the model class, which is what URL generation actually depends on); subclassing `SlugUpdateHandler` per resource (rejected — same problem we have today).

### Listener absorbs the handler — no separate `SlugUpdateHandler` service

The old design split work between the form-extension's `SlugUpdateHandler` (URL resolution, redundant-redirect cleanup, validation, persistence) and a `SlugUpdateHandlerCommand` value object that carried `(object, string oldSlug, string newSlug, string locale)`. With the trigger now in `AutomaticRedirectListener::onPreUpdate`, the listener already has every piece of data the handler needed — the subject, both slugs from the UoW diff, and the locale from the translation. Wiring those through a separate handler service buys nothing, so the listener owns the whole flow inline:

```php
final class AutomaticRedirectListener
{
    public function __construct(
        private RedirectFactoryInterface              $redirectFactory,
        private EntityManagerInterface                $entityManager,
        private AutomaticRedirectUrlResolverInterface $urlResolver,
        private RemovableRedirectFinderInterface      $removableRedirectFinder,
        private ValidatorInterface                    $validator,
        private array                                 $validationGroups,
    ) {}

    public function onPreUpdate(GenericEvent $event): void
    {
        // walk $subject->getTranslations(), diff each translation's in-memory slug
        // against $em->getUnitOfWork()->getOriginalEntityData($translation)['slug'],
        // call $this->createRedirect($subject, $oldSlug, $newSlug, $locale) per change
    }

    private function createRedirect(object $subject, string $oldSlug, string $newSlug, string $locale): void
    {
        // resolve $oldUrl / $newUrl via $this->urlResolver
        // factory: createNewWithValues($oldUrl, $newUrl, permanent: true, only404: true, channels: [])
        // remove redundant existing redirects
        // validate; bubble SlugUpdateHandlerValidationException
        // persist (controller flushes)
    }
}
```

The `SlugUpdateHandler`, `SlugUpdateHandlerInterface`, and `SlugUpdateHandlerCommand` classes are deleted. `SlugUpdateHandlerValidationException` is kept — it's the validation-error shape that bubbles out of the listener into the controller's failure UX. The `generateUrl()` abstract method and its two subclasses go away. Channels are no longer harvested from the entity — the empty list is intentional and matches today's de-facto behavior.

### Defaults baked into the new redirects: `permanent = true`, `only404 = true`, `channels = []`

- `permanent = true` is unchanged.
- `only404 = true` is new (today's automatic redirects use `false`). The proposal already lays out the rationale — self-healing on slug rollback, friendlier with downstream tooling, only pays the redirect cost on miss. Hardcoded for now; reconsider only if a concrete user need surfaces.
- `channels = []` is explicit. Today's code attempts a channel walk that produces an empty list in 100% of real-world cases (translations don't implement `ChannelsAwareInterface`). We make the empty-channels default honest instead of accidental.

### Old slug comes from Doctrine's UnitOfWork, not from a snapshot

By the time `pre_update` fires, the form has already written the new slug into the in-memory translation. The original DB-loaded slug, however, is still inside Doctrine's `UnitOfWork`:

```php
$original = $em->getUnitOfWork()->getOriginalEntityData($translation);
$oldSlug  = $original['slug'] ?? null;
$newSlug  = $translation->getSlug();
```

So the subscriber walks the resource's translations, reads the original from the UoW, compares against the in-memory value, and acts when they differ. No `\WeakMap` is needed — Doctrine already retains the snapshot for change detection.

This collapses the design to a single event (`<alias>.pre_update`). `AutomaticRedirectListener` calls `EntityManager::persist()` on each new `Redirect`; the Sylius `ResourceUpdateHandler` then runs and calls `flush()`, writing both the resource update and the new redirects atomically.

Per-locale handling falls out for free: each translation is its own entity in the UoW, so iterating `$resource->getTranslations()` and diffing each one produces one redirect per locale that actually changed.

Alternatives considered: snapshotting in a `\WeakMap` on a hypothetical `<alias>.initialize_update` event (rejected — that event does not exist in Sylius `ResourceController::updateAction`); stashing on request attributes (rejected — adds a side channel for no benefit when the UoW already has the data); a Doctrine `preUpdate` listener gated to only fire from the resource controller (rejected — gating Doctrine events by call-stack origin is fragile).

### `AutomaticRedirectListener`, registered via `kernel.event_listener` tags

The component is implemented as `Setono\SyliusRedirectPlugin\EventListener\AutomaticRedirectListener` and registered via `kernel.event_listener` tags rather than `EventSubscriberInterface`. Both are equally lazy in modern Symfony — `RegisterListenersPass` wraps either form in a `ServiceClosureArgument`, so the service is instantiated only when its event fires. We chose listener tags because the alias list is dynamic (driven by `setono_sylius_redirect.automatic_redirects`) and `EventSubscriberInterface::getSubscribedEvents()` is static. Tags are added at compile time by `ConfigureAutomaticRedirectsPass`, one `<alias>.pre_update` entry per enabled alias, so any future `SlugAware` resource works once a resolver is registered without touching the listener class.

### URL resolver receives the parent resource, not the translation

The listener calls `$this->urlResolver->resolve($subject, $slug, $locale)` with the *parent* resource (`Product`/`Taxon`), not the translation. That matches the resolver's `supports(string $class)` contract, which discriminates by the resource class — Sylius's translatable model — rather than by the translation class. The locale comes from `TranslationInterface::getLocale()` on the translation that diffed, so resolvers can route URL generation per-locale without having to inspect the subject.

### Removed translation key + templates + form-type extensions

`setono_sylius_redirect.form.add_automatic_redirect` is removed from every locale file. The two `add_automatic_redirect.html.twig` templates are deleted. `config/twig_hooks/product.yaml` and `config/twig_hooks/taxon.yaml` are deleted. Their import in `config/app/config.yaml` is removed. Anyone overriding the templates loses their override and must migrate to the config flag.

## Risks / Trade-offs

- **Risk: API/CLI/fixtures bypass the feature.** → Documented as an explicit non-goal; users who need API coverage can either drive their API through the resource controller (admin internal API) or write their own subscriber against API events. We surface this in UPGRADE.md so the limitation is visible at upgrade time.
- **Risk: per-locale slug change requires careful snapshot.** Renaming the German slug while leaving the French slug alone should produce one redirect, not two. → The per-locale `\WeakMap` shape above handles this; functional tests cover the two-locale case explicitly.
- **Risk: `only404 = true` shifts user-visible behavior.** Old slugs that still happen to resolve will not redirect — desirable but different from 2.x. → Called out as **BREAKING** in the proposal and UPGRADE.md.
- **Risk: validation exception now reaches the user as an admin error rather than a quiet form-error annotation.** → Acceptable: the controller's existing failure UX surfaces the violations; integration test asserts the admin sees the error.
- **Risk: compile-time validation against the resource registry depends on Sylius internals.** → If `RegistryInterface::has`/`get` semantics change in a future Sylius release, our extension breaks loudly at build. Acceptable; we already pin to Sylius `^2.0`.
- **Trade-off: hardcoded `permanent`/`only404`/`channels`.** Users who want different attributes have to create the redirect manually. → Acceptable for now; opening these up later is a non-breaking config addition.
- **Trade-off: `setono/composite-compiler-pass` becomes a production dependency.** → Tiny library, single-purpose, owned by the same vendor. Worth the simplification.
