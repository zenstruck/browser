# zenstruck/browser

This library provides a fluent API for Symfony functional tests. `src/Browser.php` is the shared API, `src/Browser/Session.php` coordinates requests and assertions, and `src/Browser/Session/Driver/` handles driver-specific behavior. `KernelBrowser` runs against the Symfony kernel without a real browser. `PlaywrightBrowser` drives a real browser and routes requests to the test's booted kernel.

The experimental Behat integration lives in `src/Browser/Behat/`. Mink runs scenarios with `KernelBrowser` by default and uses `PlaywrightBrowser` for `@javascript` scenarios. The Playwright adapter reuses Browser's waits and assertion predicates.

## Changes

- Keep stable public APIs backward compatible on `1.x`. Follow the PHP, Symfony, and PHPUnit constraints in `composer.json` and check `.github/workflows/ci.yml` for the tested combinations. Keep both `@test` and `#[Test]` on tests with descriptive method names so they run across supported PHPUnit versions.
- Put shared browser regressions in `tests/BrowserTests.php` or `tests/NavigationTests.php` as appropriate. Put driver-specific regressions in the corresponding browser tests and reuse the fixture kernel and pages under `tests/Fixture/`.
- Use Playwright's condition-based waits for asynchronous behavior. Keep test state and artifacts isolated across ParaTest workers.
- Update `README.md` when user-facing usage or configuration changes.
- Put Behat regressions in `tests/Behat/` and update `docs/behat.md` when setup or steps change.
- Whenever you add, change, or remove PHPUnit coverage, update the corresponding scenarios in `tests/Behat/mirror/` in the same patch. Write executable scenarios for supported behavior. For cases that need more step support, add a `@pending` scenario with the missing capability. For PHP-only cases, record a `@skip` entry with the reason.
- Keep shared Playwright scenarios in feature files used by both the `playwright` and `turbo` suites. Put Turbo-only scenarios in `tests/Behat/mirror/turbo.feature`.

## Local checks

- Install PHP dependencies with `composer install`.
- Run `vendor/bin/phpunit --exclude-group playwright` for tests that do not need a real browser. Use `vendor/bin/phpunit --filter <name>` to focus on a test or class.
- For Playwright, use Node.js 20+ and run `vendor/bin/playwright-install chromium`, then `vendor/bin/phpunit --group playwright`.
- With Chromium installed, run `vendor/bin/behat --config=tests/Behat/behat.php --format=progress --strict` for the full Behat suite. Run `vendor/bin/phpunit tests/Behat/BrowserExtensionTest.php` to check cleanup after a scenario fails.
- Run a mirror suite with `vendor/bin/behat --config=tests/Behat/behat.php --suite=turbo --strict`. Use `--tags=@pending` to see incomplete scenarios and their reasons.
- Run the full PHPUnit suite with `vendor/bin/phpunit`. CI runs `vendor/bin/paratest --functional` and runs the full Behat suite in a separate step.
- Run static analysis with `vendor/bin/phpstan analyse`.
