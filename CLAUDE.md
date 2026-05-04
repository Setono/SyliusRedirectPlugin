# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Sylius plugin for managing URL redirects (301/302). Supports channel-specific redirects, 404-only redirects, query string preservation, redirect chain resolution with infinite loop detection, and auto-redirect creation when product/taxon slugs change.

## Commands

```bash
# Run PHPUnit tests (both suites)
composer phpunit

# Run only the unit or functional suite
composer phpunit:unit
composer phpunit:functional

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

This is a standard Sylius resource plugin. The core entity is `Redirect` (`src/Model/`), registered as a Sylius resource via `SetonoSyliusRedirectPlugin` (extends `AbstractResourceBundle`, overrides `getPath()` to point at the repo root). DI configuration lives in PHP files under `config/services/`, loaded via `PhpFileLoader` from `config/services.php`; templates at `templates/`; translations at `translations/`. Each service file declares `namespace Symfony\Component\DependencyInjection\Loader\Configurator;` so `service()`, `param()`, etc. resolve without explicit `use function` imports.

### Request flow

Two event subscribers handle redirects at different stages:
- **`RequestSubscriber`** (KernelEvents::REQUEST, priority 31) - intercepts requests right after `RouterListener` (32) but before Sylius's `NonChannelLocaleListener` (10), so locale-prefix routes like `sylius_shop_homepage` matching `/anything` don't short-circuit the redirect via `setResponse()` to the default-locale homepage. Registration is gated by the `setono_sylius_redirect.allow_non_404_redirects` config option (default `true`); when set to `false`, `config/services/event_subscriber.php` skips the `$services->set(RequestSubscriber::class)` call entirely so the listener is never on the dispatcher.
- **`NotFoundSubscriber`** (KernelEvents::EXCEPTION) - catches 404 responses to apply `only404` redirects. Always registered, regardless of the toggle above.

Both use `RedirectionPathResolver` to resolve redirect chains and detect infinite loops, producing a `RedirectionPath` model that tracks the chain of visited redirects.

### Auto-redirect on slug change

`AutomaticRedirectListener` (`src/EventListener/`) listens on Sylius's `<alias>.pre_update` resource events and persists a `Redirect` whenever a translation slug changes on an admin update. The set of aliases is opt-in via the `setono_sylius_redirect.automatic_redirects` config tree (default off; e.g. `sylius.product: true`). `ConfigureAutomaticRedirectsPass` validates each alias against the Sylius resource registry at compile time and dynamically adds one `kernel.event_listener` tag per enabled alias to the listener.

URL generation goes through a composite `AutomaticRedirectUrlResolverInterface` (`src/UrlResolver/`), wired via [`setono/composite-compiler-pass`](https://github.com/Setono/composite-compiler-pass). Built-ins: `ProductAutomaticRedirectUrlResolver`, `TaxonAutomaticRedirectUrlResolver`. Third-party plugins extend coverage by tagging a service `setono_sylius_redirect.automatic_redirect_url_resolver`.

The listener inlines URL resolution, redundant-redirect cleanup, validation, and persistence — there is no separate `SlugUpdateHandler` service. Defaults baked into every produced redirect: `permanent = true`, `only404 = true`, `channels = []`, `enabled = true`. The trigger is admin-only; API/CLI/fixtures don't dispatch the resource events.

### Validation

Four custom validators prevent invalid redirects:
- `InfiniteLoopValidator` - prevents redirect chains that cycle
- `UniqueSourceValidator` - enforces source URL uniqueness for global (channel-less) redirects
- `UniqueSourcePerChannelValidator` - enforces source URL uniqueness per channel for channel-scoped redirects
- `RequireOnly404Validator` - rejects `only404 = false` whenever the `setono_sylius_redirect.allow_non_404_redirects` parameter is `false`. Paired with `RedirectType` dropping the `only404` checkbox in the same configuration so the admin form never offers a value it would later refuse.

## Testing

- **PHPUnit unit tests** (`tests/Unit/`) — pure-PHP tests with no Symfony container; mock collaborators directly. The PHPUnit `unit` testsuite runs only this directory.
- **PHPUnit functional tests** (`tests/Functional/`) — boot the Sylius test kernel from `tests/Application/`, exercise services through the container, and hit the database / HTTP layer. The PHPUnit `functional` testsuite runs only this directory.

Both suites bootstrap via `tests/Application/config/bootstrap.php`. Functional tests require MySQL.

When you add or change behavior:
- Cover it with a **unit test** whenever the logic can be exercised without a kernel — pure functions, value objects, validators, services with mockable collaborators. Aim for unit tests as the default.
- Add a **functional test** when the unit cannot represent the behavior (Sylius resource wiring, Doctrine queries against real schema, controllers, form types that depend on the container, end-to-end request flows). Skip functional tests for code already covered at the unit level.
- UI surfaces (admin form/grid/page) keep their Playwright check — see "Working in this repo" below.

Specific testing patterns:
- **Forms**: follow the [Symfony 6.4 form unit testing guide](https://symfony.com/doc/6.4/form/unit_testing.html). Extend `Symfony\Component\Form\Test\TypeTestCase`, stub form types with external dependencies (e.g. `Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType`) via a `PreloadedExtension` so the test stays in the unit suite, and assert against the synchronized model and view data after `submit()`.
- **Console commands**: follow the [Symfony 6.4 command testing guide](https://symfony.com/doc/6.4/console.html#testing-commands). Use `Symfony\Component\Console\Tester\CommandTester` against a stand-alone `Application` (no kernel) with mocked collaborators; assert on the exit code and `getDisplay()` output.
- **Mocks/stubs**: always use [Prophecy](https://github.com/phpspec/prophecy) (`use Prophecy\PhpUnit\ProphecyTrait;`, then `$this->prophesize(Foo::class)`, `->method()->willReturn(...)`, `->reveal()`). Don't reach for PHPUnit's native `createMock()` / `createStub()` — keep the doubles consistent across the suite.
- **Constraint validators**: extend `Symfony\Component\Validator\Test\ConstraintValidatorTestCase`. Implement `createValidator(): ConstraintValidatorInterface`, then assert with `$this->buildViolation(...)->atPath(...)->setParameter(...)->assertRaised()` and `$this->assertNoViolation()`. The base class wires up an execution context, sets a default `property.path` propertyPath, and chains additional violations via `buildNextViolation()`.

The test application in `tests/Application/` is a minimal Sylius app used by the functional suite and the Playwright UI checks. It requires MySQL and asset compilation to run.

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
- Before running `symfony server:start`, run `symfony server:status` first — the test app server may already be up from a previous step. Starting a second instance fails noisily; reusing the existing one (its URL is in the status output) skips a multi-second boot. `symfony server:start --dir=...` interprets the dir relative to its own internals and rejects relative paths like `tests/Application` (looks for `tests/Application/tests`); pass `$(pwd)/tests/Application` if you really need to start a fresh server.
- `symfony server:status` is **per-project**: it inspects the cwd to figure out which app to ask about. Running it from the repo root reports "Not Running" even when the test app is up, because there's no Symfony app at the root. Always run `cd tests/Application && symfony server:status` (or invoke it inside that directory) before deciding whether to start a new server.
- When you build or change a feature with a UI surface (admin form, grid, page), verify it via the Playwright MCP — boot the test app (see "Booting the test app locally"), navigate to the affected page, and confirm the rendered output before reporting the task as complete. Don't rely on PHPUnit/PHPStan/ECS alone for UI work.
- Twig extensions should split into an `Extension` (eagerly loaded, declares functions/filters) and a `Runtime` (lazily instantiated, holds dependencies and runs the logic). Wire functions via `[Runtime::class, 'method']` and tag the runtime service with `twig.runtime`. This keeps the extension cheap to load and the dependencies (e.g. repositories) only constructed when a template actually calls one.
- When adding or updating translation keys, update every locale file in `translations/` (e.g. `messages.en.yaml`, `messages.da.yaml`, ...), not just English. Missing translations leak the raw key into the UI for non-English admins.
- When you make a breaking change a plugin user would need to act on during a major-version upgrade (default-value flips, renamed/removed public classes or services, template-path moves, changed model APIs, removed config keys), document it in `UPGRADE.md` under the relevant "Upgrading from X to Y" section. Skip changes that consumers don't have to react to: dev tooling, listener-priority tweaks, translation-key additions, and other internal refactors.
- When you ship a change that affects what the plugin does for end users — new feature surfaces, new configuration keys, behavior shifts, removed UI — update `README.md` to describe the current state. `README.md` is what end users read first; it must reflect what the latest published version of the plugin does, not what an old version did. `UPGRADE.md` covers the migration path; `README.md` covers the current product.
- Before each commit, run the code-quality tools and fix what they flag: `composer fix-style` (or `composer check-style` if you only want a report), `composer analyse` (PHPStan at `level: max`), and `composer phpunit`. Don't commit on top of pre-existing failures — re-run the suite locally first so CI doesn't catch regressions you could've caught in seconds.
- For services that need a Doctrine `EntityManager`, don't inject `EntityManagerInterface` (or `setono_sylius_redirect.manager.redirect`) directly. Inject `Doctrine\Persistence\ManagerRegistry` plus the relevant `class-string` (e.g. `%setono_sylius_redirect.model.redirect.class%`) and `use Setono\Doctrine\ORMTrait;` so the manager is resolved lazily via `$this->getManager($class)`. This matches the established pattern (`src/Pruner/Pruner.php`, `src/EventListener/AutomaticRedirectListener.php`) and keeps services from binding to a single hard-coded manager.
- All new services must be registered using their FQCN as the service id (e.g. `id="Setono\SyliusRedirectPlugin\Validator\Constraints\UniqueSourceValidator"`), not a snake-cased alias like `setono_sylius_redirect.validator.unique_source`. This matches Symfony's autowiring conventions and lets consumers override or decorate by class name. The plugin still has older snake-cased ids — leave those as-is unless the surrounding work touches them, but don't add any new ones.
- When the plugin needs to configure another bundle (`sylius_grid`, `sylius_twig_hooks`, doctrine, etc.), do it from `SetonoSyliusRedirectExtension::prepend()` via `$container->prependExtensionConfig(...)` with the config inlined as a PHP array. Don't ship YAML files that consumers have to import, and don't keep the config in YAML on disk for `prepend()` to parse — the array literal lives directly inside the method so the integration point is grep-able and there is no I/O at compile time.

## OpenSpec workflow

Non-trivial features and refactors are scoped through [OpenSpec](https://github.com/Fission-AI/OpenSpec). The project tree:

- `openspec/specs/<capability>/spec.md` — current, accepted spec for each capability the plugin exposes (e.g. `automatic-redirects`).
- `openspec/changes/<change-name>/` — in-flight proposal: `proposal.md` (why), `design.md` (architectural decisions, trade-offs), `specs/<capability>/spec.md` (delta against the main spec, using `## ADDED Requirements` / `## MODIFIED Requirements` / `## REMOVED Requirements` markers), `tasks.md` (the punch list).
- `openspec/changes/archive/YYYY-MM-DD-<change-name>/` — completed changes, kept for historical context.

Slash commands available in this repo:

- `/opsx:ff <name-or-description>` — fast-forward through artifact creation; produces proposal/design/specs/tasks ready for implementation.
- `/opsx:apply [<name>]` — implement tasks from an active change, ticking off `tasks.md` as work progresses.
- `/opsx:archive [<name>]` — sync the delta spec into `openspec/specs/` and move the change folder to `openspec/changes/archive/`.

Before starting non-trivial work, check `openspec list --json` for active changes — if one already covers the work, continue it via `/opsx:apply` rather than starting fresh. After implementation, archive the change so the main specs stay current.

## Code Quality

- PHP >=8.2, targeting Symfony 6.4/7.1
- Dev tooling (PHPStan, PHPUnit, Rector, ECS, Infection) comes from `setono/sylius-plugin`
- PHPStan at `level: max` with Sylius/Symfony/Doctrine/PHPUnit/strict-rules extensions
- ECS imports `sylius-labs/coding-standard`
- All tools skip `tests/Application/`
- `declare(strict_types=1)` required in all PHP files
- CI uses the `setono/sylius-plugin/*@v2` composite GitHub Actions
