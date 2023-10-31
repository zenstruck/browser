# CHANGELOG

## [v1.6.0](https://github.com/zenstruck/browser/releases/tag/v1.6.0)

October 31st, 2023 - [v1.5.0...v1.6.0](https://github.com/zenstruck/browser/compare/v1.5.0...v1.6.0)

* 41af33b feat: support Symfony 7.0 (#134) by @kbond

## [v1.5.0](https://github.com/zenstruck/browser/releases/tag/v1.5.0)

October 23rd, 2023 - [v1.4.0...v1.5.0](https://github.com/zenstruck/browser/compare/v1.4.0...v1.5.0)

* ec27abb fix: preserving dots in query string (#132) by @norkunas
* 7b8a982 minor: allow Symfony 7.0 (#130) by @kbond
* 9b4887d minor(ci): remove php version from fixcs job by @kbond
* 2e738a6 feat: Allow to use `Psr\Container\ContainerInterface` in `->use` callback (#129) by @norkunas
* 48bb602 feat: Enable overriding of HTTP methods (#126) by @nathan-de-pachtere
* 45cc396 fix(tests): deprecations (#123) by @kbond

## [v1.4.0](https://github.com/zenstruck/browser/releases/tag/v1.4.0)

February 21st, 2023 - [v1.3.0...v1.4.0](https://github.com/zenstruck/browser/compare/v1.3.0...v1.4.0)

* a99150a doc: document `BROWSER_SOURCE_DEBUG` by @kbond
* d64b773 feat: add `KernelBrowser::assertContentType()` and prevent saving corrupt files (#121) by @welcoMattic

## [v1.3.0](https://github.com/zenstruck/browser/releases/tag/v1.3.0)

February 15th, 2023 - [v1.2.0...v1.3.0](https://github.com/zenstruck/browser/compare/v1.2.0...v1.3.0)

* 25c5ea5 fix(tests): deprecation (#119) by @kbond
* ab4b548 fix(ci): don't run fixcs/sync-with-template on forks (#119) by @kbond
* d03de6a fix: enable attaching `\SplFileInfo` objects (#119) by @kbond
* 90e99fc feat: require php 8+ (#117) by @kbond
* f4596c8 fix: Json::assertMissing/assertHas better handle empty-ish values (#116) by @flohw
* 0173c11 fix(ci): add token by @kbond
* ea600b7 chore(ci): fix by @kbond
* 3fe911f chore: update ci config (#114) by @kbond
* 9f6024a fix: tests (#113) by @kbond
* 301e072 [minor] adjust sca (#111) by @kbond
* b5f7b4a [minor] adjust `Browser::content()` and add test (#111) by @kbond
* 29fbc93 [minor] cs fixes (#111) by @kbond
* 51bb8b1 [feature] Add `Browser::content()` to fetch the raw response body (#109) by @benr77

## [v1.2.0](https://github.com/zenstruck/browser/releases/tag/v1.2.0)

August 29th, 2022 - [v1.1.0...v1.2.0](https://github.com/zenstruck/browser/compare/v1.1.0...v1.2.0)

* 0b61a6a [doc] document `assertMatchesSchema()` (#106) by @nikophil
* 29171b7 [feature] add `Json::assertMatchesSchema()` (#102) by @nikophil
* 1466827 [feature] add doubleClick and rightClick to PantherBrowser (#104) by @Chris53897, @kbond, Christopher Georg <christopher.georg@sr-travel.de>
* fc51e88 [minor] update phpstan baseline (#105) by @Chris53897, Christopher Georg <christopher.georg@sr-travel.de>
* adf645f [minor] migrate phpunit config-file to version 9 (#103) by @Chris53897, Christopher Georg <christopher.georg@sr-travel.de>
* ea84ec6 [feature] Assert that an header is not present (#98) by @nitneuk
* 157ce84 [minor] locally cache decoded json (#95) by @kbond
* c860ff9 [doc] add note/link to full `zenstruck/assert` expectation API (#95) by @kbond
* 0f88a45 [minor] cs fixes (#95) by @kbond
* cd3cce3 [doc] Fix typo in example AppBrowser use statement (#94) by @jwage
* 3858d04 [minor] small adjustments by @kbond
* 90393a9 [feature] add `Json::assertHas()`/`assertMissing()`/`assertThat()`/`assertThatEach()` (#92) by @nikophil
* 7efe89e [doc] document accessing `Crawler` instance by @kbond
* 53257c7 [minor] add Symfony 6.1 to build matrix (#90) by @kbond
* 954b858 [minor] document and help with some auth edge cases (#89) by @kbond
* 00aea30 [minor] disable profile collection by default in tests (#89) by @kbond
* 6f4a162 [minor] refactor tests (#89) by @kbond

## [v1.1.0](https://github.com/zenstruck/browser/releases/tag/v1.1.0)

April 14th, 2022 - [v1.0.0...v1.1.0](https://github.com/zenstruck/browser/compare/v1.0.0...v1.1.0)

* 582d70f [feature] add authentication assertions to `KernelBrowser` (#84) by @kbond
* d30d5d6 [doc] Readme: remove $ to allow github copy/paste of the command (#87) by @staabm
* acc0129 [minor] Browser::saveCurrentState() as "real" html (#86) by @kbond
* 6f420ae [bug] fix debugging source when exception is detected (#88) by @kbond

## [v1.0.0](https://github.com/zenstruck/browser/releases/tag/v1.0.0)

April 8th, 2022 - _[Initial Release](https://github.com/zenstruck/browser/commits/v1.0.0)_
