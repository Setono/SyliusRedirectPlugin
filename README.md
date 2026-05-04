# Sylius Redirect Plugin

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]

<a href="https://sylius.com/plugins/" target="_blank"><img src="https://sylius.com/assets/badge-approved-by-sylius.png" width="100"></a>

Gives you the ability to manage redirects in your Sylius shop.

## Installation

### Step 1: Download the plugin

Open a command console, enter your project directory and execute the following command to download the latest stable version of this plugin:

```bash
composer require setono/sylius-redirect-plugin
```

This command requires you to have Composer installed globally, as explained in the [installation chapter](https://getcomposer.org/doc/00-intro.md) of the Composer documentation.


### Step 2: Enable the plugin

Then, enable the plugin by adding it to the list of registered plugins/bundles
in the `config/bundles.php` file of your project:

```php
<?php

return [
    // ...
    
    // Add before SyliusGridBundle
    Setono\SyliusRedirectPlugin\SetonoSyliusRedirectPlugin::class => ['all' => true],
    Sylius\Bundle\GridBundle\SyliusGridBundle::class => ['all' => true],
    
    // ...
];
```

It is **IMPORTANT** to add the plugin before the grid bundle else you will get a an exception saying `You have requested a non-existent parameter "setono_sylius_redirect.model.redirect.class".`

### Step 3: Import routes
```yaml
# config/routes/setono_sylius_redirect.yaml

setono_sylius_redirect_admin:
    resource: "@SetonoSyliusRedirectPlugin/config/admin_routing.yaml"
    prefix: /%sylius_admin.path_name%
```

### Step 4: Update database

Use Doctrine migrations to create a migration file and update the database.

```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
```

### Step 5: Install assets
```bash
bin/console assets:install
```

## What it does

This plugin allows you to create new redirects.

Under the new menu entry `Redirects` unders `Configuration`, you can manage redirects.

### Redirection entry

An entry is composed by:
* Source url, relative to your website
* Target URL, can be relative or absolute in case you want to redirect to another website
* Permanent or Temporary (This impact the HTTP response code of the redirection, 301 or 302)
* Enabled
* Redirect only if 404 (to manage potentially dead links)

### Security

There is a built-in security when creating/modifying redirection that prevent creating an infinite loop. This work with infinite recursive checking.

A second security is to prevent same source redirection leading to inconstant redirect.

### Automatic redirects on slug changes

When you opt in, the plugin creates a redirect every time an admin renames
the slug of a configured Sylius resource. Opt-in is per resource alias and
defaults to **off everywhere**:

```yaml
# config/packages/setono_sylius_redirect.yaml
setono_sylius_redirect:
    automatic_redirects:
        sylius.product: true
        sylius.taxon: true
```

Aliases must implement
`Sylius\Component\Resource\Model\SlugAwareInterface`. Unknown aliases or
non-slug-aware models fail container compilation with a clear error.

Each automatic redirect is created with these fixed defaults:
`permanent = true`, `only404 = true`, no channel scope, `enabled = true`.
`only404 = true` means the redirect kicks in only when the request would
otherwise 404 — so if the slug is later rolled back to its original value,
the entry self-heals without you having to clean up. The plugin also
collapses chains: if you rename `a → b` and then `b → c`, the earlier
`a → b` redirect is replaced with `a → c`.

The trigger is admin-only: only edits that flow through Sylius's
`ResourceController::updateAction` create redirects. API edits, fixture
loads, CLI scripts, and direct repository writes do not.

#### Adding coverage for your own resources

To redirect on slug changes for a custom resource, register a service
implementing `Setono\SyliusRedirectPlugin\UrlResolver\AutomaticRedirectUrlResolverInterface`
and tag it `setono_sylius_redirect.automatic_redirect_url_resolver`:

```php
final class BlogPostAutomaticRedirectUrlResolver implements AutomaticRedirectUrlResolverInterface
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function supports(string $class): bool
    {
        return is_a($class, BlogPostInterface::class, true);
    }

    public function resolve(object $resource, string $slug, string $locale): string
    {
        return $this->urlGenerator->generate('app_blog_post_show', [
            'slug' => $slug,
            '_locale' => $locale,
        ]);
    }
}
```

...then enable the alias under `automatic_redirects: app.blog_post: true`.

## Development

This project uses [OpenSpec](https://github.com/Fission-AI/OpenSpec) to plan and track non-trivial features. Active proposals live under `openspec/changes/<name>/`; the current accepted specs live under `openspec/specs/<capability>/`; completed proposals move to `openspec/changes/archive/`. If you're contributing a sizeable change, write the proposal / design / spec / tasks artifacts there first so the why and the contract are clear before any code lands.

[ico-version]: https://img.shields.io/packagist/v/setono/sylius-redirect-plugin.svg
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg
[ico-github-actions]: https://github.com/Setono/SyliusRedirectPlugin/workflows/build/badge.svg

[link-packagist]: https://packagist.org/packages/setono/sylius-redirect-plugin
[link-github-actions]: https://github.com/Setono/SyliusRedirectPlugin/actions
