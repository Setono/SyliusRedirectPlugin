## 1. Dependencies and configuration

- [x] 1.1 Add `setono/composite-compiler-pass` to the production `require` block in `composer.json` and run `composer update --lock`
- [x] 1.2 Extend `Setono\SyliusRedirectPlugin\DependencyInjection\Configuration` with an `automatic_redirects` node — an associative `prototype('boolean')` keyed by Sylius resource alias, defaulting to an empty array
- [x] 1.3 In `SetonoSyliusRedirectExtension::load`, read the `automatic_redirects` config and set the parameter `setono_sylius_redirect.automatic_redirects` (associative array of alias => bool, normalized to drop any `false` entries)
- [x] 1.4 In a compiler pass running after `Sylius\Bundle\ResourceBundle\DependencyInjection\Compiler\RegisterResourcesPass` (so that the `sylius.resources` parameter is fully populated), validate every configured alias — throw `Symfony\Component\Config\Definition\Exception\InvalidConfigurationException` for unknown aliases, and again for aliases whose model does not implement `Sylius\Component\Resource\Model\SlugAwareInterface`

## 2. URL resolver extension point

- [x] 2.1 Create `src/UrlResolver/AutomaticRedirectUrlResolverInterface.php` with `supports(string $class): bool` and `resolve(object $resource, string $slug, string $locale): string`
- [x] 2.2 Create `src/UrlResolver/AutomaticRedirectUrlResolver.php` (the composite) extending `Setono\CompositeCompilerPass\CompositeService` and implementing `AutomaticRedirectUrlResolverInterface` — walks children on each `resolve()`, throws `RuntimeException` if no child supports the class
- [x] 2.3 Create `src/UrlResolver/ProductAutomaticRedirectUrlResolver.php` — supports `ProductInterface` (or its concrete model class via `%sylius.model.product.class%`), generates `sylius_shop_product_show` with `slug` + `_locale`
- [x] 2.4 Create `src/UrlResolver/TaxonAutomaticRedirectUrlResolver.php` — supports `TaxonInterface` (or its concrete model class), generates `sylius_shop_product_index` with `slug` + `_locale`
- [x] 2.5 Use `Setono\CompositeCompilerPass\CompositeCompilerPass` directly (no wrapper) to gather services tagged `setono_sylius_redirect.automatic_redirect_url_resolver` into `setono_sylius_redirect.url_resolver.automatic_redirect.composite`
- [x] 2.6 Register the compiler pass in `Setono\SyliusRedirectPlugin\SetonoSyliusRedirectPlugin::build()`
- [x] 2.7 Create `config/services/url_resolver.xml` registering the composite (alias the interface to it), the two built-in resolvers tagged for the composite, and add an `<import>` for it in `config/services.xml`
- [x] 2.8 Resolver-coverage validation is deferred to the runtime composite: it throws `RuntimeException` on `resolve()` when no child supports the class. Compile-time validation here would require instantiating tagged resolvers, which is fragile

## 3. Listener owns redirect creation inline (no separate handler)

- [x] 3.1 Delete the entire `src/SlugUpdateHandler/` directory: `SlugUpdateHandler.php`, `SlugUpdateHandlerInterface.php`, `SlugUpdateHandlerCommand.php`, plus the previously-removed `ProductTranslationSlugUpdateHandler.php` and `TaxonTranslationSlugUpdateHandler.php`. Keep only `Setono\SyliusRedirectPlugin\Exception\SlugUpdateHandlerValidationException` (still raised by the listener)
- [x] 3.2 Delete `config/services/slug_update_handler.xml` and remove its `<import>` from `config/services.xml`
- [x] 3.3 Inline the former handler responsibilities into a private `createRedirect(object $subject, string $oldSlug, string $newSlug, string $locale): void` on `AutomaticRedirectListener`: resolve old/new URLs via the composite resolver, build the `Redirect` via `RedirectFactoryInterface::createNewWithValues($oldUrl, $newUrl, permanent: true, only404: true, channels: [])`, run `RemovableRedirectFinderInterface` cleanup, validate with `ValidatorInterface` and let `SlugUpdateHandlerValidationException` bubble, then `EntityManager::persist()`

## 4. ResourceController event listener

- [x] 4.1 Create `src/EventListener/AutomaticRedirectListener.php` (a kernel event listener, not a subscriber — see design.md for why). Per-alias `kernel.event_listener` tags are added at compile time by `ConfigureAutomaticRedirectsPass` from the injected `automatic_redirects` parameter, so the alias list stays dynamic
- [x] 4.2 Inject six dependencies — `RedirectFactoryInterface`, `EntityManagerInterface`, `AutomaticRedirectUrlResolverInterface`, `RemovableRedirectFinderInterface`, `ValidatorInterface`, and `%setono_sylius_redirect.form.type.redirect.validation_groups%`
- [x] 4.3 In `onPreUpdate(GenericEvent $event)`, walk the event subject's translations (via `TranslatableInterface::getTranslations()`), read each translation's original slug from `EntityManagerInterface::getUnitOfWork()->getOriginalEntityData($translation)['slug']`, compare against the in-memory `$translation->getSlug()`, and on each diff call the private `createRedirect()` method with the parent resource, old slug, new slug, and locale. Skip translations whose UoW original data is empty (newly persisted, no DB slug yet)
- [x] 4.4 Register the listener in `config/services/event_subscriber.xml`; the `kernel.event_listener` tags are added per-alias by `ConfigureAutomaticRedirectsPass` at compile time

## 5. Removal of the form-driven path

- [x] 5.1 Delete `src/Form/Extension/AutomaticRedirectTypeExtension.php`, `src/Form/Extension/ProductTranslationTypeExtension.php`, `src/Form/Extension/TaxonTranslationTypeExtension.php`
- [x] 5.2 Remove the corresponding `<service>` entries from `config/services/form.xml`
- [x] 5.3 Delete `templates/admin/Product/add_automatic_redirect.html.twig` and `templates/admin/Taxon/add_automatic_redirect.html.twig` — also delete the empty `templates/admin/Product/` and `templates/admin/Taxon/` directories if they have no other content
- [x] 5.4 Delete `config/twig_hooks/product.yaml` and `config/twig_hooks/taxon.yaml` and remove their imports from `config/app/config.yaml`
- [x] 5.5 Remove the `setono_sylius_redirect.form.add_automatic_redirect` translation key from every locale file under `translations/` (`messages.{en,da,fr,it,nl}.yaml`)
- [x] 5.6 Delete `src/Twig/EventSubscriber/ProductFormComponentSubscriber.php` and `src/Twig/EventSubscriber/TaxonFormComponentSubscriber.php` (and their empty parent directory) and the matching `<service>` entries in `config/services/twig.xml` — they only exist to surface the now-deleted `addAutomaticRedirect` form field on the LiveComponent re-render and have no purpose without the form extensions

## 6. Tests

- [ ] 6.1 Unit-test the composite `AutomaticRedirectUrlResolver` — first matching child wins; throws when no child supports the class (`tests/Unit/UrlResolver/AutomaticRedirectUrlResolverTest.php`)
- [ ] 6.2 Unit-test the `ProductAutomaticRedirectUrlResolver` and `TaxonAutomaticRedirectUrlResolver` against a mocked `UrlGeneratorInterface` (Prophecy)
- [ ] 6.3 Unit-test `AutomaticRedirectListener::createRedirect` (via integration through `onPreUpdate`) — given `oldSlug !== newSlug`, the redirect is created with `permanent = true`, `only404 = true`, empty channels, the resolver is consulted twice (once per slug), and the validator + redundant-redirect cleanup are invoked
- [ ] 6.4 Unit-test `AutomaticRedirectListener::onPreUpdate` — only acts on translations whose UoW original slug differs from the in-memory slug; produces one redirect per changed locale; resolves URLs against the parent resource; tolerates non-translatable subjects by returning quietly
- [ ] 6.5 Functional test: enable `automatic_redirects: { sylius.product: true }`, drive the admin Product update flow, assert one `Redirect` row exists with the expected source/destination/`only404 = true`/empty channels (`tests/Functional/AutomaticRedirect/ProductSlugChangeTest.php`)
- [ ] 6.6 Functional test: with the alias not enabled, drive the same admin update flow and assert no `Redirect` row is created
- [ ] 6.7 Functional test: change two locales in one save and assert two redirects appear, one per locale
- [ ] 6.8 Functional test: container compilation fails when `automatic_redirects: { sylius.unknown: true }` is configured (boot a kernel, expect `InvalidConfigurationException`)

## 7. Documentation and release plumbing

- [ ] 7.1 Update `UPGRADE.md` under "Upgrading from 2.x to 3.0" with: form extensions/templates removed, `setono_sylius_redirect.automatic_redirects` is the new opt-in (default off), `SlugUpdateHandler::generateUrl()` removed in favor of `AutomaticRedirectUrlResolverInterface`, behavior change to `only404 = true` and empty channels
- [ ] 7.2 Update `CLAUDE.md` "Architecture" section: replace the "Auto-redirect on slug change" paragraph with a description of the resource-event subscriber and the new config flag
- [ ] 7.3 Update `README.md` to describe the new `setono_sylius_redirect.automatic_redirects` config (what it does, default off, how to opt in `sylius.product` / `sylius.taxon`, the fixed `only404 = true` / empty-channels defaults) and the `AutomaticRedirectUrlResolverInterface` extension point. Remove any mention of the admin-form "Add automatic redirect" checkbox and the matching templates
- [ ] 7.4 Run `composer fix-style`, `composer analyse`, `composer phpunit` and fix anything they flag — gates must be green before committing

## 8. Verify in the Playwright test app

- [ ] 8.1 Boot the test app, enable `automatic_redirects: { sylius.product: true }` in its config, edit a product slug in the admin, and confirm via the redirects index that a new `Redirect` was persisted with the expected source/destination
- [ ] 8.2 Confirm the admin Product/Taxon edit forms no longer render the "Add automatic redirect" checkbox
