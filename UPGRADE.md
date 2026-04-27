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

[twig-hooks]: https://docs.sylius.com/the-customization-guide/customization/twig-hooks
