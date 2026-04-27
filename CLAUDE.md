# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Sylius plugin for managing URL redirects (301/302). Supports channel-specific redirects, 404-only redirects, query string preservation, redirect chain resolution with infinite loop detection, and auto-redirect creation when product/taxon slugs change.

## Commands

```bash
# Run PHPUnit tests
composer phpunit

# Run a single PHPUnit test
vendor/bin/phpunit tests/path/to/TestFile.php
vendor/bin/phpunit --filter testMethodName

# Static analysis (PHPStan at level: max)
composer analyse

# Code style check / fix (ECS with Sylius coding standard)
composer check-style
composer fix-style

# Rector dry-run
vendor/bin/rector --dry-run
```

## Architecture

### Plugin structure

This is a standard Sylius resource plugin. The core entity is `Redirect` (`src/Model/`), registered as a Sylius resource via `SetonoSyliusRedirectPlugin` (extends `AbstractResourceBundle`, overrides `getPath()` to point at the repo root). DI configuration lives in XML files under `config/services/`; templates at `templates/`; translations at `translations/`.

### Request flow

Two event subscribers handle redirects at different stages:
- **`RequestSubscriber`** (KernelEvents::REQUEST, priority 31) - intercepts requests right after `RouterListener` (32) but before Sylius's `NonChannelLocaleListener` (10), so locale-prefix routes like `sylius_shop_homepage` matching `/anything` don't short-circuit the redirect via `setResponse()` to the default-locale homepage.
- **`NotFoundSubscriber`** (KernelEvents::EXCEPTION) - catches 404 responses to apply `only404` redirects

Both use `RedirectionPathResolver` to resolve redirect chains and detect infinite loops, producing a `RedirectionPath` model that tracks the chain of visited redirects.

### Auto-redirect on slug change

`ProductTranslationSlugUpdateHandler` and `TaxonTranslationSlugUpdateHandler` hook into product/taxon edit forms via form extensions. When a slug changes, they automatically create a redirect from the old URL to the new one.

### Validation

Three custom validators prevent invalid redirects:
- `InfiniteLoopValidator` - prevents redirect chains that cycle
- `SourceValidator` - enforces source URL uniqueness
- `SourceRegexValidator` - validates against Sylius shop security regex

## Testing

- **PHPUnit** (`tests/`) - unit tests, bootstrapped via `tests/Application/config/bootstrap.php`

The test application in `tests/Application/` is a minimal Sylius app used for integration testing and the Playwright UI checks. It requires MySQL and asset compilation to run.

### Booting the test app locally

```bash
cd tests/Application
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:schema:create
php bin/console sylius:fixtures:load default --no-interaction  # admin login: sylius / sylius
php bin/console assets:install public
yarn install && yarn build
symfony server:start                                            # https://127.0.0.1:8000/admin
```

## Working in this repo

- Always use relative paths in shell commands. Absolute paths inside this working directory trigger a Claude Code permission prompt for the user; relative paths run without one.
- If you've changed directory (e.g. into `tests/Application/`) for a previous step, return to the project root before subsequent commands so relative paths still resolve correctly. Don't try to compensate by prepending an absolute path — `cd` back to the root instead.
- Run the test-app console from the project root via `./tests/Application/bin/console <cmd>` instead of `cd tests/Application && php bin/console <cmd>`. It avoids the `cd` round-trip and keeps the working directory at the project root for any follow-up commands.
- When you build or change a feature with a UI surface (admin form, grid, page), verify it via the Playwright MCP — boot the test app (see "Booting the test app locally"), navigate to the affected page, and confirm the rendered output before reporting the task as complete. Don't rely on PHPUnit/PHPStan/ECS alone for UI work.
- Twig extensions should split into an `Extension` (eagerly loaded, declares functions/filters) and a `Runtime` (lazily instantiated, holds dependencies and runs the logic). Wire functions via `[Runtime::class, 'method']` and tag the runtime service with `twig.runtime`. This keeps the extension cheap to load and the dependencies (e.g. repositories) only constructed when a template actually calls one.
- When adding or updating translation keys, update every locale file in `translations/` (e.g. `messages.en.yaml`, `messages.da.yaml`, ...), not just English. Missing translations leak the raw key into the UI for non-English admins.
- When you make a breaking change a plugin user would need to act on during a major-version upgrade (default-value flips, renamed/removed public classes or services, template-path moves, changed model APIs, removed config keys), document it in `UPGRADE.md` under the relevant "Upgrading from X to Y" section. Skip changes that consumers don't have to react to: dev tooling, listener-priority tweaks, translation-key additions, and other internal refactors.
- Before each commit, run the code-quality tools and fix what they flag: `composer fix-style` (or `composer check-style` if you only want a report), `composer analyse` (PHPStan at `level: max`), and `composer phpunit`. Don't commit on top of pre-existing failures — re-run the suite locally first so CI doesn't catch regressions you could've caught in seconds.

## Code Quality

- PHP >=8.2, targeting Symfony 6.4/7.1
- Dev tooling (PHPStan, PHPUnit, Rector, ECS, Infection) comes from `setono/sylius-plugin`
- PHPStan at `level: max` with Sylius/Symfony/Doctrine/PHPUnit/strict-rules extensions
- ECS imports `sylius-labs/coding-standard`
- All tools skip `tests/Application/`
- `declare(strict_types=1)` required in all PHP files
- CI uses the `setono/sylius-plugin/*@v2` composite GitHub Actions
