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

# Behat (requires MySQL + test app setup)
vendor/bin/behat
```

## Architecture

### Plugin structure

This is a standard Sylius resource plugin. The core entity is `Redirect` (`src/Model/`), registered as a Sylius resource via `SetonoSyliusRedirectPlugin` (extends `AbstractResourceBundle`, overrides `getPath()` to point at the repo root). DI configuration lives in XML files under `config/services/`; templates at `templates/`; translations at `translations/`.

### Request flow

Two event subscribers handle redirects at different stages:
- **`ControllerSubscriber`** (KernelEvents::CONTROLLER) - intercepts requests before the controller runs to apply standard redirects
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
- **Behat** (`features/`) - integration tests requiring a full Sylius test application (`tests/Application/`)

The test application in `tests/Application/` is a minimal Sylius app used for Behat and integration tests. It requires MySQL and asset compilation to run.

## Code Quality

- PHP >=8.2, targeting Symfony 6.4/7.1
- Dev tooling (PHPStan, PHPUnit, Rector, ECS, Infection) comes from `setono/sylius-plugin`
- PHPStan at `level: max` with Sylius/Symfony/Doctrine/PHPUnit/strict-rules extensions
- ECS imports `sylius-labs/coding-standard`
- All tools skip `tests/Application/`
- `declare(strict_types=1)` required in all PHP files
- CI uses the `setono/sylius-plugin/*@v2` composite GitHub Actions
