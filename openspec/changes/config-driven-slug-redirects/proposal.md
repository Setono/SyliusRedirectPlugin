## Why

The "automatic redirect on slug change" feature for Products and Taxons is wired through Symfony form-type extensions: a checkbox is rendered in the admin translation tab, and the redirect is only created when an admin checks it before saving. This couples the trigger to specific form types, requires per-save user attention, and ships templates/translations that have to be maintained for the only remaining purpose of toggling a feature. The behavior should be a configuration choice on the plugin, applied transparently when admins update Products or Taxons in the backend.

## What Changes

- Move the trigger from `AutomaticRedirectTypeExtension` (form lifecycle) to a Sylius `ResourceController` event subscriber that listens on `<alias>.initialize_update` (snapshot the old slug) and `<alias>.post_update` (act on it).
- Introduce a plugin-level config tree `setono_sylius_redirect.automatic_redirects` keyed by Sylius resource alias (e.g. `sylius.product`, `sylius.taxon`), each with a boolean value. Default for every alias is `false` — the feature is off until explicitly opted in.
- Validate at compile time that every configured alias both exists in the Sylius resource registry and resolves to a model implementing `Sylius\Component\Resource\Model\SlugAwareInterface`.
- Introduce `Setono\SyliusRedirectPlugin\UrlResolver\AutomaticRedirectUrlResolverInterface` plus a composite implementation built via `setono/composite-compiler-pass`. Ship two concrete resolvers (Product, Taxon) tagged for the composite. Concrete resolvers reduce to the URL-generation logic that today lives in `ProductTranslationSlugUpdateHandler` / `TaxonTranslationSlugUpdateHandler`.
- Collapse `SlugUpdateHandler` and its two abstract subclasses into a single concrete handler that delegates URL building to the resolver. Every redirect produced by this handler is created with `permanent = true`, `only404 = true`, and an empty `channels` collection — these are intentionally not configurable.
- Drop the validation-error-as-form-error path: the new handler bubbles `SlugUpdateHandlerValidationException` to the resource controller, which surfaces it to the admin like any other update failure.
- **BREAKING** Remove the form-type extensions, the two `add_automatic_redirect.html.twig` templates, the matching twig hooks, and the `setono_sylius_redirect.form.add_automatic_redirect` translation key.
- **BREAKING** Remove the `SlugUpdateHandler::generateUrl()` abstract method along with `ProductTranslationSlugUpdateHandler` and `TaxonTranslationSlugUpdateHandler`. Consumers extending the abstract handler migrate to the new resolver interface.
- **BREAKING** Behavior change: automatic redirects now ship with `only404 = true` (they only fire when the source URL would 404) and no channel scoping. Previously they were created with `only404 = false` and a best-effort channel walk that always produced an empty list in practice. Self-healing on slug rollback is now the default.

## Capabilities

### New Capabilities
- `automatic-redirects`: Configuration-driven creation of `Redirect` records when an admin updates the slug of a configured Sylius resource (default coverage: Product and Taxon). Defines the configuration shape, the URL-resolver extension point, and the resulting redirect's defaults (`permanent`, `only404`, `channels`).

### Modified Capabilities
<!-- No existing specs — this is the first formal capability captured for the plugin. -->

## Impact

- **Code added**: `src/UrlResolver/AutomaticRedirectUrlResolverInterface.php`, a composite implementation, `ProductAutomaticRedirectUrlResolver` / `TaxonAutomaticRedirectUrlResolver`, an `AutomaticRedirectListener` (kernel event listener) that owns redirect creation inline, a compiler pass for the composite, and config-tree extensions in `Configuration` / `SetonoSyliusRedirectExtension`.
- **Code removed**: `src/Form/Extension/AutomaticRedirectTypeExtension.php`, `ProductTranslationTypeExtension`, `TaxonTranslationTypeExtension`, `ProductTranslationSlugUpdateHandler`, `TaxonTranslationSlugUpdateHandler`, the abstract `generateUrl()` on `SlugUpdateHandler` (and the entire `src/SlugUpdateHandler/` directory — `SlugUpdateHandler`, `SlugUpdateHandlerInterface`, `SlugUpdateHandlerCommand`), `src/Twig/EventSubscriber/ProductFormComponentSubscriber.php`, `src/Twig/EventSubscriber/TaxonFormComponentSubscriber.php`, `templates/admin/Product/add_automatic_redirect.html.twig`, `templates/admin/Taxon/add_automatic_redirect.html.twig`, `config/twig_hooks/product.yaml`, `config/twig_hooks/taxon.yaml`, `config/services/slug_update_handler.xml`, the `form.type_extension` services in `config/services/form.xml`, and the two `kernel.event_subscriber` entries in `config/services/twig.xml`.
- **Translations**: Remove `setono_sylius_redirect.form.add_automatic_redirect` from every locale file under `translations/`.
- **Dependencies**: Add `setono/composite-compiler-pass` to `composer.json` (production dependency).
- **Configuration**: New `setono_sylius_redirect.automatic_redirects` tree. Existing keys (`driver`, `remove_after`, `resources`) are unaffected.
- **UPGRADE.md**: New "Upgrading from 2.x to 3.0" entry covering the form-extension/template removal, the new config flag (off by default), the `SlugUpdateHandler` API change, and the behavioral shift to `only404 = true`.
- **API contract**: `SlugUpdateHandlerInterface` and `SlugUpdateHandlerCommand` keep their public shape; only the abstract handler's `generateUrl()` extension point goes away.
- **Tests**: New unit tests for the composite resolver and the resource-event subscriber (with stubbed handler). New functional test that drives the Sylius admin product/taxon update flow and asserts a `Redirect` row appears with the expected source/destination/`only404`/empty channels when the feature is enabled, and does not appear when it is off.
