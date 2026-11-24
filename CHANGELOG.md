# Change Log
All notable changes to this project will be documented in this file.
Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [Unreleased][unreleased]

### Added

* Documentation site https://tomaj.github.io/omnipay-vub/ (VitePress): getting started, configuration, operations, signatures, test mode, API reference, FAQ; its examples live in `examples/` and run in the test suite ([#11](https://github.com/tomaj/omnipay-vub/issues/11)).
* The documentation site at presentation level: logo, favicon set and social card, hero illustration, payment-flow diagram, pages for the payment flow, starting a payment, the return URL, error handling, testing your integration, security and upgrading from 3.x; five runnable examples in `examples/<name>/` (payment form, custom pay button, return URL, specific symbol, full redirect flow against a local fake bank) that the new `Examples` workflow and `ExamplesTest` run against the code, and every inline PHP snippet of README, UPGRADE-4.0.md and the docs runs in `DocsSnippetsTest` ([#16](https://github.com/tomaj/omnipay-vub/issues/16)).
* `LICENSE.md` (MIT, as declared in `composer.json`), `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, `SECURITY.md`, `RELEASING.md`, issue and pull request templates; `composer.json` has `support` links and suggests an HTTP client for Omnipay ([#5](https://github.com/tomaj/omnipay-vub/issues/5)).
* PHPUnit test suite: purchase data and HMAC signature (expected values computed independently with the `openssl` CLI), production/test endpoints, complete purchase with valid, failed and tampered signatures ([#4](https://github.com/tomaj/omnipay-vub/pull/4)).
* Tests for every public accessor, the package exceptions, `PurchaseResponse` without a `PurchaseRequest` and non-string response data: 99 % statement coverage (CI requires 90 %), Infection MSI 100 % (CI requires `minMsi` and `minCoveredMsi` 95) ([#10](https://github.com/tomaj/omnipay-vub/issues/10)).
* Rector, PHPStan deprecation rules and Infection in `require-dev`; composer scripts `phpunit`, `coverage`, `infection`, `rector`, `rector-fix` with descriptions; `check` also runs Rector ([#7](https://github.com/tomaj/omnipay-vub/issues/7)).
* GitHub Actions CI (tests on PHP 8.4 - 8.6 with lowest dependencies on 8.4, nightly 8.7, 90 % statement coverage, Infection, Rector), PHP-CS-Fixer (PER-CS), PHPStan level max with strict rules, `composer audit`, Dependabot ([#4](https://github.com/tomaj/omnipay-vub/pull/4), [#6](https://github.com/tomaj/omnipay-vub/issues/6)).
* Tag-driven release workflow (GitHub release with CHANGELOG notes, source archives and build provenance), CodeQL and OpenSSF Scorecard workflows ([#6](https://github.com/tomaj/omnipay-vub/issues/6)).

### Changed

* **BREAKING CHANGE**: `purchase()` requires the `amount` and throws `InvalidRequestException('The amount parameter is required')` without it, instead of signing a payment with an empty `AMT` that VÚB rejects after the redirect ([#19](https://github.com/tomaj/omnipay-vub/issues/19)).
* **BREAKING CHANGE**: Requires PHP 8.4 or newer (no PHP constraint before; Travis tested PHP 5.6 and 7.0) ([#4](https://github.com/tomaj/omnipay-vub/pull/4), [#8](https://github.com/tomaj/omnipay-vub/issues/8)).
* **BREAKING CHANGE**: Requires `omnipay/common` ^3.2 (was ~3.0) ([#4](https://github.com/tomaj/omnipay-vub/pull/4)).
* **BREAKING CHANGE**: Parameter getters and setters are typed: getters return `?string`, setters take `?string` and return `static`; `HmacSign::sign()` takes and returns `string`. Code that extends these classes must match the new signatures ([#4](https://github.com/tomaj/omnipay-vub/pull/4)).
* **BREAKING CHANGE**: Setters are called with the declared `?string` type: passing an `int` (e.g. `setVs(123)`) from a file with `declare(strict_types=1)` now throws a `TypeError`; values passed through `initialize()`/`purchase([...])` are still coerced. An `int` variable symbol is now posted as a string (`"123"`), the signature is unchanged ([#4](https://github.com/tomaj/omnipay-vub/pull/4)).
* **BREAKING CHANGE**: `Gateway`, `PurchaseRequest`, `PurchaseResponse`, `CompletePurchaseRequest`, `CompletePurchaseResponse` and `HmacSign` are `final` (`HmacSign` also `readonly`); compose them instead of extending. See [UPGRADE-4.0.md](https://github.com/tomaj/omnipay-vub/blob/master/UPGRADE-4.0.md) ([#9](https://github.com/tomaj/omnipay-vub/issues/9)).
* **BREAKING CHANGE**: `CompletePurchaseRequest` reads the bank's return parameters from the HTTP request passed to the gateway (Omnipay's default is `Request::createFromGlobals()`) instead of `$_GET`; frameworks and long-running servers must pass the current request to the gateway ([#9](https://github.com/tomaj/omnipay-vub/issues/9)).
* **BREAKING CHANGE**: `PurchaseResponse::getRedirectUrl()` throws `Omnipay\Eplatby\Exception\LogicException` (a `\LogicException` implementing the new package interface `Omnipay\Eplatby\Exception\EplatbyException`, which extends Omnipay's `OmnipayException`) instead of a bare `\LogicException` ([#9](https://github.com/tomaj/omnipay-vub/issues/9)).
* `Gateway::purchase()`/`completePurchase()` return `PurchaseRequest`/`CompletePurchaseRequest`, and the requests' `send()` returns `PurchaseResponse`/`CompletePurchaseResponse` (narrower return types, no change for callers) ([#9](https://github.com/tomaj/omnipay-vub/issues/9)).
* `CompletePurchaseRequest`: a missing (or non-string) `VS`, `RES` or `SIGN` query parameter no longer emits "undefined array key" warnings; it is treated as an empty string, so a missing `SIGN` fails the signature check and a missing `VS`/`RES` makes `getVs()`/`getRes()` return `''` instead of `null` ([#4](https://github.com/tomaj/omnipay-vub/pull/4)).

### Removed

* The Composer dist archive contains only the library (`src/`, `composer.json`, README, LICENSE, CHANGELOG, UPGRADE): tests, CI and tool configs are `export-ignore`d. The ad-hoc scripts `test.php` and `testserver.php` are removed; the documentation has working examples ([#5](https://github.com/tomaj/omnipay-vub/issues/5)).
* The no-op `PurchaseRequest::initialize()` override (the parent implementation is used) ([#4](https://github.com/tomaj/omnipay-vub/pull/4)).
* Travis CI configuration, the unused `omnipay/tests` and `squizlabs/php_codesniffer` dev dependencies ([#4](https://github.com/tomaj/omnipay-vub/pull/4)).

### Security

* **Security**: **BREAKING CHANGE**: The specific symbol is part of both signatures, as VÚB specifies: the payment request signs `MID + AMT + VS + SS + CS + RURL` (was `MID + AMT + VS + CS + RURL`) and the return signs `VS + SS + RES` (was `VS + RES`). Payments without a specific symbol sign exactly as before; payments with one were rejected by VÚB until now. `completePurchase()` takes the optional `ss` you sent in `purchase()` and throws `InvalidRequestException('Specific symbol mismatch')` when the returned `SS` differs (also when you sent none and the return has one, so digits cannot be moved between `VS` and `SS`); `CompletePurchaseResponse::getSs()` returns it. Thanks to @senik, who confirmed the order with VÚB ([#2](https://github.com/tomaj/omnipay-vub/pull/2)).
* **Security**: **BREAKING CHANGE**: `completePurchase()` requires the `vs` parameter (the variable symbol of the order being completed) and throws `InvalidRequestException('Variable symbol mismatch')` when the bank's signed `VS` differs, so a genuine return URL of one order can no longer complete another. Without `vs` it throws `InvalidRequestException('The vs parameter is required')`. The bank does not sign the amount: load the order by its variable symbol and use the amount stored with it. See [UPGRADE-4.0.md](https://github.com/tomaj/omnipay-vub/blob/master/UPGRADE-4.0.md) ([#13](https://github.com/tomaj/omnipay-vub/issues/13)).
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
