## Context

The plugin's `RequestSubscriber` listens on `KernelEvents::REQUEST` (priority 31) and queries the `redirects` table on every main request, filtered by `only404 = false`. For installations that only use 404-path redirects, this is a per-request DB hit they cannot opt out of. Issue #113 asks for a configuration toggle.

The plugin already exposes other top-level config under `setono_sylius_redirect.*` (e.g. `remove_after`, `automatic_redirects`) wired through `Configuration` → `SetonoSyliusRedirectExtension`. Service definitions live in PHP files under `config/services/`, loaded by `PhpFileLoader` via `config/services.php`. `event_subscriber.php` is where `RequestSubscriber` and `NotFoundSubscriber` are currently registered side-by-side.

## Goals / Non-Goals

**Goals:**
- Allow operators to disable the always-on `RequestSubscriber` via configuration.
- Default behavior is unchanged: `RequestSubscriber` is registered (and the per-request query runs) unless the operator opts out.
- When disabled, the listener is *not registered* — no `KernelEvents::REQUEST` callback at all, no early-return guard inside the subscriber.
- `NotFoundSubscriber` is unaffected.

**Non-Goals:**
- Locking the `only404` field in the admin form when the toggle is off.
- Adding a validator that rejects `only404 = false` on persisted `Redirect` rows when the toggle is off. Operators who flip the flag are responsible for understanding that existing non-404 redirects in the DB simply won't fire.
- Caching, bloom filters, or any other transparent optimization of the request path.
- Changing the default in 3.x. The default stays `true`.

## Decisions

### Decision: Gate the service registration, not the subscriber's body

`config/services/event_subscriber.php` reads the parameter and only calls `$services->set(RequestSubscriber::class)...` when the parameter is `true`. When `false`, the subscriber class is never registered, so the dispatcher has no listener to invoke.

**Alternatives considered:**
- *Inject the flag and early-return inside `onKernelRequest`*: still pays the dispatcher dispatch cost and requires an extra constructor arg. Conditional registration is strictly cheaper and removes the listener from `debug:event-dispatcher` output, which is the right signal to give operators.
- *Add a `kernel.event_subscriber` tag conditionally via a compiler pass*: the PHP service file already has access to the parameter, so a pass adds complexity for no gain.

### Decision: Read the parameter inside the PHP service file

The Symfony `ContainerConfigurator` supports `param('setono_sylius_redirect.allow_non_404_redirects')`, but conditionally registering a service requires reading the *resolved* value at load time. The extension passes the value into the service file via a parameter (`$container->setParameter(...)` in `SetonoSyliusRedirectExtension::load`), and `event_subscriber.php` reads it from the container builder using `$container->parameters()->...` is not available — instead we use the `ContainerConfigurator`'s host container by accepting the underlying `ContainerBuilder`. The simplest path: have `SetonoSyliusRedirectExtension::load` set a parameter, then load the PHP file, and have the file read the parameter via the second argument to its closure (`ContainerBuilder $builder`).

If the closure signature in `event_subscriber.php` is `static function (ContainerConfigurator $container): void`, we extend it to `static function (ContainerConfigurator $container, ContainerBuilder $builder): void` (the loader supports the second argument). The file then reads `$builder->getParameter('setono_sylius_redirect.allow_non_404_redirects')` and branches on it.

**Alternative considered:** wire the conditional in `SetonoSyliusRedirectExtension::load` itself (after `loader->load(...)`), removing the service definition from the container when the flag is off. Rejected: spreading service-definition logic between the extension and the service file makes both harder to read. Keep it co-located with the rest of the service registrations.

### Decision: Parameter name `setono_sylius_redirect.allow_non_404_redirects`

Matches the user-facing intent ("are non-404 redirects allowed at all?") and lines up with the config key. Other candidates:
- `intercept_all_requests` — describes mechanism, not intent. Rejected.
- `only_404_redirects` — double-negative when reading the disable case (`only_404_redirects: true` to *disable* something). Rejected.

## Risks / Trade-offs

- **[Risk]** Operators who set `allow_non_404_redirects: false` while the DB still contains `only404 = false` redirects will see those rows go silently dormant. → **Mitigation:** README documents this. No validator/form lock — keeping the change minimal per the maintainer's scope decision.
- **[Risk]** A future contributor adding a new request-time subscriber may not realize the toggle exists. → **Mitigation:** the spec ties the toggle to the `KernelEvents::REQUEST` non-404 path generally, not just the current class name; design.md and the README make the intent explicit.
- **[Trade-off]** A flag-driven conditional registration is slightly less discoverable than a transparent optimization (e.g. caching). Accepted: operators asked for an explicit knob.

## Migration Plan

- Default value is `true`. Existing installations see no behavior change on upgrade. No `UPGRADE.md` entry needed.
- Operators who want the optimization set `setono_sylius_redirect: { allow_non_404_redirects: false }` in their config.
- Rollback: remove the config entry (or set it to `true`); `RequestSubscriber` is registered again on the next cache rebuild.
