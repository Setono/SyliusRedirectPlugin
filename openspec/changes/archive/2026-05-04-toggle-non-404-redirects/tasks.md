## 1. Configuration

- [x] 1.1 Add a boolean `allow_non_404_redirects` node to `src/DependencyInjection/Configuration.php` with default value `true` and a `->info()` line explaining it.
- [x] 1.2 In `SetonoSyliusRedirectExtension::load()`, set the resolved value as a container parameter named `setono_sylius_redirect.allow_non_404_redirects`.

## 2. Conditional service registration

- [x] 2.1 Update the closure signature in `config/services/event_subscriber.php` to accept `ContainerBuilder $builder` as the second argument.
- [x] 2.2 Read `$builder->getParameter('setono_sylius_redirect.allow_non_404_redirects')` and only call `$services->set(RequestSubscriber::class)...->tag('kernel.event_subscriber')` when the value is `true`.
- [x] 2.3 Confirm `NotFoundSubscriber`'s registration is not gated by the parameter.

## 3. Tests

- [x] 3.1 Add a unit test under `tests/Unit/DependencyInjection/` that builds the extension with the default config and asserts the container parameter equals `true` and that a `RequestSubscriber` definition is present.
- [x] 3.2 Extend that test with a second case: build the extension with `allow_non_404_redirects: false` and assert the parameter equals `false` and `RequestSubscriber` has no definition in the container, while `NotFoundSubscriber` does.
- [x] 3.3 Add a Configuration unit test asserting that a non-boolean value (e.g. `"yes"`) causes `InvalidConfigurationException`.

## 4. Documentation

- [x] 4.1 Update `README.md` with a "Disabling non-404 redirects" section: what the option does, default value, when to flip it, and the caveat that any existing `only404 = false` redirects in the DB go dormant when disabled.

## 5. Quality gates

- [x] 5.1 Run `composer fix-style` and ensure no diff is required afterwards.
- [x] 5.2 Run `composer analyse` (PHPStan at `level: max`) and resolve any findings.
- [x] 5.3 Run `composer phpunit` (both suites) and ensure everything is green.
