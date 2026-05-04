# non-404-redirects Specification

## Purpose

Governs whether the plugin matches redirects on non-404 (200) requests and the configuration toggle that enables or disables that behavior. The 404-only redirect path (handled by `NotFoundSubscriber`) is out of scope — this capability concerns only the always-on `RequestSubscriber` and its registration gate.

## Requirements

### Requirement: Configuration toggle for non-404 redirect matching

The plugin SHALL expose a `setono_sylius_redirect.allow_non_404_redirects` boolean configuration node with a default value of `true`. The configured value SHALL be exposed at compile time as the container parameter `setono_sylius_redirect.allow_non_404_redirects`.

#### Scenario: Default value is true

- **WHEN** the container is built with no `allow_non_404_redirects` key
- **THEN** the container parameter `setono_sylius_redirect.allow_non_404_redirects` MUST equal `true`

#### Scenario: Explicit false is honored

- **WHEN** the container is built with `setono_sylius_redirect: { allow_non_404_redirects: false }`
- **THEN** the container parameter `setono_sylius_redirect.allow_non_404_redirects` MUST equal `false`

#### Scenario: Non-boolean values fail validation

- **WHEN** the container is built with `setono_sylius_redirect: { allow_non_404_redirects: "yes" }`
- **THEN** container compilation MUST fail with a `Symfony\Component\Config\Definition\Exception\InvalidConfigurationException`

### Requirement: Conditional registration of the request-time redirect subscriber

When `setono_sylius_redirect.allow_non_404_redirects` is `true`, the plugin SHALL register `Setono\SyliusRedirectPlugin\EventSubscriber\RequestSubscriber` as a `kernel.event_subscriber` so that it listens on `KernelEvents::REQUEST`. When the parameter is `false`, the plugin SHALL NOT register `RequestSubscriber` in the container at all; the service definition itself MUST be omitted, not merely tagged differently or short-circuited at runtime.

#### Scenario: Subscriber is registered when toggle is true

- **WHEN** the container is built with `allow_non_404_redirects: true` (the default)
- **THEN** the container MUST contain a service definition for `Setono\SyliusRedirectPlugin\EventSubscriber\RequestSubscriber` tagged `kernel.event_subscriber`

#### Scenario: Subscriber is absent when toggle is false

- **WHEN** the container is built with `allow_non_404_redirects: false`
- **THEN** the container MUST NOT contain a service definition for `Setono\SyliusRedirectPlugin\EventSubscriber\RequestSubscriber`, and the event dispatcher MUST NOT have any listener on `KernelEvents::REQUEST` originating from this plugin

#### Scenario: 404-path subscriber is unaffected by the toggle

- **WHEN** the container is built with `allow_non_404_redirects: false`
- **THEN** the container MUST still contain a service definition for `Setono\SyliusRedirectPlugin\EventSubscriber\NotFoundSubscriber` tagged `kernel.event_subscriber`
