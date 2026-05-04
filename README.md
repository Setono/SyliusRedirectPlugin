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

It is **IMPORTANT** to add the plugin before the grid bundle, otherwise you will get an exception saying `You have requested a non-existent parameter "setono_sylius_redirect.model.redirect.class".`

### Step 3: Import routes
```yaml
# config/routes/setono_sylius_redirect.yaml

setono_sylius_redirect:
    resource: "@SetonoSyliusRedirectPlugin/config/routes.yaml"
```

The imported file already prefixes the admin routes with `/%sylius_admin.path_name%`, so no extra `prefix:` is needed.

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

Under the new menu entry `Redirects` under `Configuration`, you can manage redirects.

### Redirection entry

An entry is composed by:
* **Source URL** — must start with `/` (relative to your shop)
* **Destination URL** — relative or absolute (you can redirect to another host)
* **Permanent / Temporary** — drives the HTTP status code (301 or 302)
* **Enabled** — disabled rows are ignored at request time
* **Only when 404** — only fire the redirect when the request would otherwise 404, useful for cleaning up dead inbound links
* **Keep query string** — append the inbound query string to the destination (defaults to `true`)
* **Channels** — restrict the redirect to one or more channels; leave empty to match every channel

### Security

There is a built-in safeguard when creating/modifying a redirect that prevents
infinite loops. It walks the redirect chain recursively and refuses to save a
redirect that would cycle.

A second safeguard prevents two enabled redirects from sharing the same
source URL within the same channel scope (or globally, if neither uses
channels) — otherwise the runtime would have to pick between two
inconsistent destinations. The admin form additionally probes the AJAX
endpoint `/admin/ajax/redirects/check-source` while you type and surfaces
a warning with a link to the conflicting redirect, so you can spot the
collision before submitting.

### Disabling non-404 redirects

By default the plugin checks for a matching redirect on every request, so a
`only404 = false` redirect can intercept the request before the controller
runs. That convenience comes at the cost of one database query per request,
even on installations that exclusively use `only404 = true` redirects.

If you only ever use 404-only redirects, disable the always-on listener:

```yaml
# config/packages/setono_sylius_redirect.yaml
setono_sylius_redirect:
    allow_non_404_redirects: false # default: true
```

When `false`, the plugin no longer registers its `KernelEvents::REQUEST`
listener — there is zero per-request overhead. Redirects continue to work
on the 404 path, which is all you need when every redirect has
`only404 = true`. Note: any existing `only404 = false` rows in the database
will go silently dormant until you re-enable the option, so flip it only if
you're sure none of your live redirects rely on the always-on path.

### Pruning unused redirects

The bundled `setono:sylius-redirect:prune` command deletes redirects that
have not been accessed in the last *N* days. Configure the threshold under
`remove_after`:

```yaml
# config/packages/setono_sylius_redirect.yaml
setono_sylius_redirect:
    remove_after: 90 # days; 0 (the default) disables pruning
```

Then schedule the command (cron, `messenger` cron, deploy hook, …):

```bash
bin/console setono:sylius-redirect:prune
```

Pruning iterates eligible redirects in batches via
[`ocramius/doctrine-batch-utils`][doctrine-batch-utils], so it stays
memory-safe even on tables with millions of rows.

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

[doctrine-batch-utils]: https://github.com/Ocramius/DoctrineBatchUtils
