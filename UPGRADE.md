# Upgrade Guide

This document describes breaking and otherwise notable changes between major
versions of the plugin. When upgrading, follow each section in order.

## Upgrading from 2.x to 3.0

### Requirements

- PHP `>= 8.2`
- Symfony `6.4` or `7.x`
- Sylius `2.x`

The plugin no longer supports Sylius 1.x or PHP `< 8.2`.

### Default value changed on `Redirect`

The default for `Setono\SyliusRedirectPlugin\Model\Redirect::$keepQueryString`
flipped from `false` to `true` to better match how the feature is most
commonly used. If your fixtures, factories, or migrations rely on the old
default, set the value explicitly.

### Plugin file layout aligned with the Sylius 2.x plugin skeleton

`Resources/{config,translations,views}` was moved to the repository root:

| 2.x                        | 3.0           |
| -------------------------- | ------------- |
| `Resources/config/`        | `config/`     |
| `Resources/translations/`  | `translations/` |
| `Resources/views/`         | `templates/`  |

`SetonoSyliusRedirectPlugin` overrides `getPath()` so bundle-relative lookups
(`@SetonoSyliusRedirectPlugin/...`) continue to resolve. It also overrides
`getConfigFilesPath()` to point Doctrine's mapping loader at the new
`config/doctrine/` location.

### Template paths

The admin templates were rebuilt around [Sylius Twig hooks][twig-hooks]. If
you previously overrode the 2.x templates directly, port your customizations
to the matching hook.

### Pruning command renamed and moved behind `PrunerInterface`

The `setono:sylius-redirect:remove` command was renamed to
`setono:sylius-redirect:prune`. The class was renamed in turn:

| 2.x                                                    | 3.0                                          |
| ------------------------------------------------------ | -------------------------------------------- |
| `Setono\SyliusRedirectPlugin\Command\RemoveRedirectsCommand` | `Setono\SyliusRedirectPlugin\Command\PruneCommand` |

Update any cron jobs, deploy scripts, or service overrides that referenced
the old name.

The pruning logic moved out of the repository and into a dedicated
`Setono\SyliusRedirectPlugin\Pruner\PrunerInterface` (default implementation:
`Setono\SyliusRedirectPlugin\Pruner\Pruner`). It now iterates eligible
redirects with [`ocramius/doctrine-batch-utils`][doctrine-batch-utils], so
prunes stay memory-safe on large tables. As a consequence,
`RedirectRepositoryInterface::removeNotAccessed()` was removed — call
`PrunerInterface::prune()` instead.

### Automatic redirects on slug changes are now config-driven

The "Add automatic redirect" checkbox on the admin Product/Taxon translation
forms is gone. The plugin now creates redirects automatically when a slug
changes on an admin update — but only for the resource aliases you opt in
under a new configuration key:

```yaml
setono_sylius_redirect:
    automatic_redirects:
        sylius.product: true
        sylius.taxon: true
```

The default is "off everywhere": if `automatic_redirects` is missing or
empty, no automatic redirects are created. Aliases must implement
`Sylius\Component\Resource\Model\SlugAwareInterface`; the plugin fails the
container build with an `InvalidConfigurationException` for unknown aliases
or non-slug-aware models.

The defaults baked into every automatic redirect are now `permanent = true`,
`only404 = true`, and an empty channel scope. `only404 = true` is a
**behavior change**: in 2.x the form-driven path used `false`. Old slugs
that still resolve will no longer redirect, which is usually what you want
— it self-heals if the slug is later rolled back. If you need different
attributes, create the redirect manually.

### Removed classes, services, templates, and translation keys

| Removed                                                                                  | Replacement                                                              |
| ---------------------------------------------------------------------------------------- | ------------------------------------------------------------------------ |
| `Setono\SyliusRedirectPlugin\SlugUpdateHandler\SlugUpdateHandler` (and `Interface`, `Command`) | Inlined into `Setono\SyliusRedirectPlugin\EventListener\AutomaticRedirectListener` |
| `Setono\SyliusRedirectPlugin\SlugUpdateHandler\ProductTranslationSlugUpdateHandler`      | `AutomaticRedirectListener` + `ProductAutomaticRedirectUrlResolver`      |
| `Setono\SyliusRedirectPlugin\SlugUpdateHandler\TaxonTranslationSlugUpdateHandler`        | `AutomaticRedirectListener` + `TaxonAutomaticRedirectUrlResolver`        |
| `Setono\SyliusRedirectPlugin\Form\Extension\AutomaticRedirectTypeExtension`              | (none — opt-in is config-driven)                                         |
| `Setono\SyliusRedirectPlugin\Form\Extension\ProductTranslationTypeExtension`             | (none)                                                                   |
| `Setono\SyliusRedirectPlugin\Form\Extension\TaxonTranslationTypeExtension`               | (none)                                                                   |
| `Setono\SyliusRedirectPlugin\Twig\EventSubscriber\ProductFormComponentSubscriber`        | (none — only existed to expose the removed checkbox)                     |
| `Setono\SyliusRedirectPlugin\Twig\EventSubscriber\TaxonFormComponentSubscriber`          | (none)                                                                   |
| Templates `templates/admin/Product/add_automatic_redirect.html.twig` and `templates/admin/Taxon/add_automatic_redirect.html.twig` | (none)                          |
| Twig hooks `config/twig_hooks/product.yaml` and `config/twig_hooks/taxon.yaml`           | (none)                                                                   |
| Translation key `setono_sylius_redirect.form.add_automatic_redirect`                     | (none)                                                                   |
| `Setono\SyliusRedirectPlugin\Validator\Constraints\SourceRegex` (and `SourceRegexValidator`) | (none — sources are no longer regex-validated against `sylius.security.shop_regex`) |
| Translation key `setono_sylius_redirect.form.redirect.source.source_regex`               | (none)                                                                   |
| `Setono\SyliusRedirectPlugin\Validator\Constraints\Source` (and `SourceValidator`)       | Split into `UniqueSource` (global redirects) and `UniqueSourcePerChannel` (channel-scoped redirects). Both ship with their own validators, both are registered on `Redirect` in `config/validation/Redirect.xml`. |
| Translation key `setono_sylius_redirect.form.redirect.source_already_existing`           | Replaced by `setono_sylius_redirect.form.redirect.source.unique_globally` and `setono_sylius_redirect.form.redirect.source.unique_per_channel` (the latter exposes a `{{ channel }}` placeholder for the conflicting channel code). |
| `@SetonoSyliusRedirectPlugin/config/app/config.yaml` (and the no-op `config/grids.yaml`) | Removed — the plugin's grid and twig-hook configuration is now registered automatically via `SetonoSyliusRedirectExtension::prepend()`. Drop the matching `imports:` entry from your `config/packages/setono_sylius_redirect.yaml`. |
| `@SetonoSyliusRedirectPlugin/config/admin_routing.yaml`                                  | Replaced by `@SetonoSyliusRedirectPlugin/config/routes.yaml`. The new file already applies the `/%sylius_admin.path_name%` prefix, so the consumer no longer needs a `prefix:` line — change the route import to `resource: "@SetonoSyliusRedirectPlugin/config/routes.yaml"` and remove the explicit prefix. |

If you wrote your own slug-update handler subclassing `SlugUpdateHandler`
or generating URLs via the abstract `generateUrl()`, port the URL logic
to a service implementing
`Setono\SyliusRedirectPlugin\UrlResolver\AutomaticRedirectUrlResolverInterface`
and tag it `setono_sylius_redirect.automatic_redirect_url_resolver`. The
composite resolver routes `resolve()` calls to the first child whose
`supports(string $class): bool` returns true.

### Scope: admin only

Automatic redirects fire only through Sylius's `ResourceController::updateAction`
event stream (`<alias>.pre_update`). API edits, fixture loads, CLI scripts,
or repository-level writes do not trigger redirect creation. If you need
that, write your own listener against the relevant event (Doctrine
lifecycle, API platform, etc.) and wire it through the same
`AutomaticRedirectUrlResolverInterface` composite.

[twig-hooks]: https://docs.sylius.com/the-customization-guide/customization/twig-hooks
[doctrine-batch-utils]: https://github.com/Ocramius/DoctrineBatchUtils
