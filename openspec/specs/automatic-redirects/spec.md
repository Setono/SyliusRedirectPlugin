# automatic-redirects Specification

## Purpose
TBD - created by archiving change config-driven-slug-redirects. Update Purpose after archive.
## Requirements
### Requirement: Configuration tree for opting into automatic redirects per resource

The plugin SHALL expose a `setono_sylius_redirect.automatic_redirects` configuration node accepting an associative array keyed by Sylius resource alias with boolean values. The default value of every alias is `false`. Aliases not listed are treated as `false`.

#### Scenario: Configuration validates against the Sylius resource registry

- **WHEN** the container is built and the configuration contains an alias not registered in `Sylius\Resource\Metadata\RegistryInterface`
- **THEN** container compilation MUST fail with a `Symfony\Component\Config\Definition\Exception\InvalidConfigurationException` whose message names the offending alias

#### Scenario: Configuration rejects aliases whose model is not slug-aware

- **WHEN** the container is built and the configuration contains an alias whose registered model does not implement `Sylius\Component\Resource\Model\SlugAwareInterface`
- **THEN** container compilation MUST fail with an `InvalidConfigurationException` whose message names the offending alias and its model class

#### Scenario: Aliases with no matching URL resolver fail loudly on first use

- **WHEN** the configuration enables an alias whose model class is not `supports()`-ed by any service tagged `setono_sylius_redirect.automatic_redirect_url_resolver`, and an admin then triggers an update for that resource
- **THEN** the composite `AutomaticRedirectUrlResolver` MUST throw a `RuntimeException` whose message names the offending class

#### Scenario: Empty configuration is valid

- **WHEN** the container is built with no `automatic_redirects` key, or with an empty `automatic_redirects: {}` mapping
- **THEN** the container MUST build successfully and no automatic-redirect behavior is wired

### Requirement: Automatic redirect creation on Sylius admin slug update

When a Sylius resource configured under `automatic_redirects: <alias>: true` is updated through `Sylius\Bundle\ResourceBundle\Controller\ResourceController::updateAction` and the slug of one or more locale-specific translations changes, the plugin SHALL persist a new `Redirect` for each changed locale before the controller's HTTP response is sent. Detection and persistence are performed by `Setono\SyliusRedirectPlugin\EventListener\AutomaticRedirectListener` listening on `<alias>.pre_update`; the listener compares each translation's in-memory slug against `Doctrine\ORM\UnitOfWork::getOriginalEntityData()`. No `<alias>.initialize_update` event is used and no separate handler service exists — the listener owns the URL resolution, redundant-redirect cleanup, validation, and persistence inline.

#### Scenario: Slug change in a single locale produces a single redirect

- **WHEN** an admin saves a Product whose `automatic_redirects: sylius.product` flag is `true`, the English translation slug changes from `old-slug` to `new-slug`, and no other locales change
- **THEN** the resulting `Redirect` row MUST have `source` equal to the URL produced by the registered resolver for the old English slug, `destination` equal to the URL for the new English slug, `permanent = true`, `only404 = true`, an empty `channels` collection, and `enabled = true`

#### Scenario: Slug change in multiple locales produces one redirect per locale

- **WHEN** an admin saves a Product with `automatic_redirects: sylius.product = true`, the English slug changes from `old-en` to `new-en`, and the German slug simultaneously changes from `old-de` to `new-de`
- **THEN** exactly two `Redirect` rows MUST be persisted — one English, one German — each with `permanent = true`, `only404 = true`, and empty channels

#### Scenario: Save without a slug change creates no redirect

- **WHEN** an admin saves a configured resource and no translation's slug is different from its pre-edit value
- **THEN** the plugin MUST NOT persist any `Redirect` row

#### Scenario: Resource configured as false produces no redirect

- **WHEN** an admin saves a Product whose `automatic_redirects: sylius.product` flag is `false` (or absent) and the English slug changes
- **THEN** the plugin MUST NOT persist any `Redirect` row

#### Scenario: Validation failure surfaces to the admin

- **WHEN** the persisted `Redirect` would violate a constraint (for example, a duplicate source after redundant-redirect cleanup)
- **THEN** `AutomaticRedirectListener` MUST raise `Setono\SyliusRedirectPlugin\Exception\SlugUpdateHandlerValidationException` from `<alias>.pre_update` and MUST NOT persist the new `Redirect`

#### Scenario: Existing redirect targeting the old URL is removed before persistence

- **WHEN** an admin saves a configured resource, its slug changes from `old` to `new`, and a `Redirect` already exists where `destination` matches the old URL (i.e. an earlier `previous → old` redirect)
- **THEN** the plugin MUST remove the earlier `Redirect` and persist a new `previous → new` redirect, collapsing the chain

### Requirement: URL resolver extension point for automatic redirects

The plugin SHALL expose an `Setono\SyliusRedirectPlugin\UrlResolver\AutomaticRedirectUrlResolverInterface` with two methods: `supports(string $class): bool` and `resolve(object $resource, string $slug, string $locale): string`. A composite implementation collected via `setono/composite-compiler-pass` SHALL aggregate every service tagged `setono_sylius_redirect.automatic_redirect_url_resolver`.

#### Scenario: Composite delegates to the first matching resolver

- **WHEN** `resolve($product, 'foo', 'en_US')` is called on the composite and one tagged resolver returns `true` from `supports(Product::class)`
- **THEN** the composite MUST return the value produced by that resolver's `resolve()` call

#### Scenario: Composite throws when no resolver supports the resource

- **WHEN** `resolve($custom, 'foo', 'en_US')` is called on the composite and no tagged resolver returns `true` from `supports(get_class($custom))`
- **THEN** the composite MUST throw a `RuntimeException` whose message names the unsupported class

#### Scenario: Built-in Product resolver produces the shop product URL

- **WHEN** `ProductAutomaticRedirectUrlResolver::resolve($product, 'galactic-pulse-t-shirt', 'en_US')` is called
- **THEN** the result MUST equal the URL generated by `UrlGeneratorInterface::generate('sylius_shop_product_show', ['slug' => 'galactic-pulse-t-shirt', '_locale' => 'en_US'])`

#### Scenario: Built-in Taxon resolver produces the shop taxon index URL

- **WHEN** `TaxonAutomaticRedirectUrlResolver::resolve($taxon, 'mens-collection', 'en_US')` is called
- **THEN** the result MUST equal the URL generated by `UrlGeneratorInterface::generate('sylius_shop_product_index', ['slug' => 'mens-collection', '_locale' => 'en_US'])`

### Requirement: Automatic redirects ship with fixed defaults

The plugin SHALL hardcode the following attributes on every `Redirect` it produces from a slug change: `permanent = true`, `only404 = true`, `channels = []`, `enabled = true`. These values MUST NOT be configurable per resource.

#### Scenario: Persisted redirect always carries the fixed defaults

- **WHEN** any automatic-redirect path persists a `Redirect`
- **THEN** that record MUST satisfy `isPermanent() === true`, `isOnly404() === true`, `getChannels()->isEmpty() === true`, and `isEnabled() === true`

