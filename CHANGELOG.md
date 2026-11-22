# Change Log
All notable changes to this project will be documented in this file.
Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [Unreleased][unreleased]

### Added

* `LICENSE.md` (MIT, as declared in `composer.json`), `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, `SECURITY.md`, `RELEASING.md`, issue and pull request templates; `composer.json` has `support` links and suggests an HTTP client for Omnipay ([#5](https://github.com/tomaj/omnipay-vub/issues/5)).
* PHPUnit test suite: purchase data and HMAC signature (expected values computed independently with the `openssl` CLI), production/test endpoints, complete purchase with valid, failed and tampered signatures ([#4](https://github.com/tomaj/omnipay-vub/pull/4)).
* Rector, PHPStan deprecation rules and Infection in `require-dev`; composer scripts `phpunit`, `coverage`, `infection`, `rector`, `rector-fix` with descriptions; `check` also runs Rector ([#7](https://github.com/tomaj/omnipay-vub/issues/7)).
* GitHub Actions CI (tests on PHP 8.3 - 8.6, nightly 8.7, lowest dependencies), PHP-CS-Fixer (PER-CS), PHPStan level max with strict rules, `composer audit`, Dependabot ([#4](https://github.com/tomaj/omnipay-vub/pull/4)).

### Changed

* **BREAKING CHANGE**: Requires PHP 8.3 or newer (no PHP constraint before; Travis tested PHP 5.6 and 7.0) ([#4](https://github.com/tomaj/omnipay-vub/pull/4)).
* **BREAKING CHANGE**: Requires `omnipay/common` ^3.2 (was ~3.0) ([#4](https://github.com/tomaj/omnipay-vub/pull/4)).
* **BREAKING CHANGE**: Parameter getters and setters are typed: getters return `?string`, setters take `?string` and return `static`; `HmacSign::sign()` takes and returns `string`. Code that extends these classes must match the new signatures ([#4](https://github.com/tomaj/omnipay-vub/pull/4)).
* **BREAKING CHANGE**: Setters are called with the declared `?string` type: passing an `int` (e.g. `setVs(123)`) from a file with `declare(strict_types=1)` now throws a `TypeError`; values passed through `initialize()`/`purchase([...])` are still coerced. An `int` variable symbol is now posted as a string (`"123"`), the signature is unchanged ([#4](https://github.com/tomaj/omnipay-vub/pull/4)).
* `CompletePurchaseRequest`: a missing (or non-string) `VS`, `RES` or `SIGN` query parameter no longer emits "undefined array key" warnings; it is treated as an empty string, so a missing `SIGN` fails the signature check and a missing `VS`/`RES` makes `getVs()`/`getRes()` return `''` instead of `null` ([#4](https://github.com/tomaj/omnipay-vub/pull/4)).

### Removed

* The Composer dist archive contains only the library (`src/`, `composer.json`, README, LICENSE, CHANGELOG, UPGRADE): tests, CI and tool configs are `export-ignore`d. The ad-hoc scripts `test.php` and `testserver.php` are removed; the documentation has working examples ([#5](https://github.com/tomaj/omnipay-vub/issues/5)).
* The no-op `PurchaseRequest::initialize()` override (the parent implementation is used) ([#4](https://github.com/tomaj/omnipay-vub/pull/4)).
* Travis CI configuration, the unused `omnipay/tests` and `squizlabs/php_codesniffer` dev dependencies ([#4](https://github.com/tomaj/omnipay-vub/pull/4)).

### Security

* **Security**: `CompletePurchaseRequest` compares the `SIGN` query parameter with `hash_equals()` instead of `!=`, so numeric-looking or non-string signatures no longer pass a loose comparison ([#4](https://github.com/tomaj/omnipay-vub/pull/4)).

## [3.1.0] - 2022-08-24

### Added

* `testHost` gateway parameter: the host of the test-mode endpoint (default `https://platby.tomaj.sk`), so test mode can point to your own simulator ([#3](https://github.com/tomaj/omnipay-vub/pull/3)).

## [3.0.0] - 2019-02-26

### Changed

* **BREAKING CHANGE**: Requires `omnipay/common` 3 (was 2). Follow the Omnipay 3 upgrade guide (HTTP client, `Omnipay\Common\Http\ClientInterface`) ([#1](https://github.com/tomaj/omnipay-vub/pull/1)).
* **BREAKING CHANGE**: PHP 5.5 is no longer supported (tested on PHP 5.6 - 7.1).

## [0.1.0] - 2017-01-09

Initial release. Omnipay 2 gateway `Eplatby` for VÚB e-platby (VÚB banka, Slovakia):

* `purchase()` redirects the customer to VÚB e-platby with the payment signed by HMAC-SHA256.
* `completePurchase()` verifies the `SIGN` of the bank's return request and exposes the variable symbol (`getVs()`) and result (`getRes()`).
* Test mode sends payments to a test server instead of VÚB.

[unreleased]: https://github.com/tomaj/omnipay-vub/compare/3.1.0...HEAD
[3.1.0]: https://github.com/tomaj/omnipay-vub/compare/3.0.0...3.1.0
[3.0.0]: https://github.com/tomaj/omnipay-vub/compare/0.1.0...3.0.0
[0.1.0]: https://github.com/tomaj/omnipay-vub/releases/tag/0.1.0
