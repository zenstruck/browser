# zenstruck/browser

This library provides a fluent API for Symfony functional tests. `src/Browser.php` is the shared API, `src/Browser/Session.php` coordinates requests and assertions, and `src/Browser/Session/Driver/` handles driver-specific behavior. `KernelBrowser` runs against the Symfony kernel without a real browser. `PlaywrightBrowser` drives a real browser and routes requests to the test's booted kernel.

## Changes

- Keep stable public APIs backward compatible on `1.x`. Follow the PHP, Symfony, and PHPUnit constraints in `composer.json` and check `.github/workflows/ci.yml` for the tested combinations. Keep both `@test` and `#[Test]` on tests with descriptive method names so they run across supported PHPUnit versions.
- Put shared browser regressions in `tests/BrowserTests.php` or `tests/NavigationTests.php` as appropriate. Put driver-specific regressions in the corresponding browser tests and reuse the fixture kernel and pages under `tests/Fixture/`.
- Use Playwright's condition-based waits for asynchronous behavior. Keep test state and artifacts isolated across ParaTest workers.
- Update `README.md` when user-facing usage or configuration changes.

## Local checks

- Install PHP dependencies with `composer install`.
- Run `vendor/bin/phpunit --exclude-group playwright` for tests that do not need a real browser. Use `vendor/bin/phpunit --filter <name>` to focus on a test or class.
- For Playwright, use Node.js 20+ and run `vendor/bin/playwright-install chromium`, then `vendor/bin/phpunit --group playwright`.
- Run the full suite with `vendor/bin/phpunit`. CI also runs `vendor/bin/paratest --functional`.
- Run static analysis with `vendor/bin/phpstan analyse`.
