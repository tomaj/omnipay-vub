# Changelog

## Unreleased

### Changed (BC break)

- Requires PHP 8.3 or newer (no PHP constraint before; Travis tested PHP 5.6 and 7.0).
- Requires `omnipay/common` ^3.2 (was ~3.0).
- Parameter getters and setters are typed: getters return `?string`, setters take `?string` and return `static`;
  `HmacSign::sign()` takes and returns `string`. Code that extends these classes must match the new signatures.
- `CompletePurchaseRequest` compares the `SIGN` query parameter with `hash_equals()` instead of `!=`.
  A missing (or non-string) `VS`, `RES` or `SIGN` query parameter no longer emits "undefined array key" warnings;
  it is treated as an empty string, so a missing `SIGN` fails the signature check and a missing `VS`/`RES` makes
  `getVs()`/`getRes()` return `''` instead of `null`.
- Setters are called with the declared `?string` type: passing an `int` (e.g. `setVs(123)`) from a file with
  `declare(strict_types=1)` now throws a `TypeError`; values passed through `initialize()`/`purchase([...])`
  are still coerced. An `int` variable symbol is now posted as a string (`"123"`), the signature is unchanged.
- The no-op `PurchaseRequest::initialize()` override is removed (the parent implementation is used).

### Added

- PHPUnit test suite: purchase data and HMAC signature (expected values computed independently with the
  `openssl` CLI), production/test endpoints, complete purchase with valid, failed and tampered signatures.
- GitHub Actions CI (tests on PHP 8.3 - 8.6, nightly 8.7, lowest dependencies), PHP-CS-Fixer (PER-CS),
  PHPStan level max with strict rules, `composer audit`, Dependabot.

### Removed

- Travis CI configuration, the unused `omnipay/tests` and `squizlabs/php_codesniffer` dev dependencies.
