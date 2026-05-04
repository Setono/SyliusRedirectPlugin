## Why

Today, every main HTTP request triggers a database query against the `redirects` table to find a non-404 redirect that matches the URL — even on installations that never use non-404 redirects. Operators that exclusively rely on `only404 = true` redirects pay this per-request cost for a feature they don't use, and have no way to opt out. A configuration toggle lets them turn the always-on `RequestSubscriber` off, so only the existing 404-path subscriber runs. See https://github.com/Setono/SyliusRedirectPlugin/issues/113.

## What Changes

- Add a new boolean configuration option `setono_sylius_redirect.allow_non_404_redirects`, defaulting to `true` (preserves current behavior, including in 3.x).
- When the option is `true`, `Setono\SyliusRedirectPlugin\EventSubscriber\RequestSubscriber` is registered as today.
- When the option is `false`, the plugin MUST NOT register `RequestSubscriber` on the event dispatcher at all. The `KernelEvents::REQUEST` listener disappears entirely; no DB query runs on the hot path. The `NotFoundSubscriber` (404-path) keeps working unchanged.
- README documents the option (purpose, default, when to disable).
- No `UPGRADE.md` entry: default is unchanged.

## Capabilities

### New Capabilities
- `non-404-redirects`: Governs whether the plugin matches redirects on non-404 (200) requests and the configuration toggle that enables or disables that behavior.

### Modified Capabilities
<!-- none -->

## Impact

- `src/DependencyInjection/Configuration.php` — new boolean node.
- `src/DependencyInjection/SetonoSyliusRedirectExtension.php` — exposes the option as a container parameter and reads it when loading service definitions.
- `config/services/event_subscriber.php` — registers `RequestSubscriber` only when the parameter is `true`.
- `README.md` — documents the option.
- Tests: assert that `RequestSubscriber` is registered when the flag is `true` (default) and absent from the container when the flag is `false`.
