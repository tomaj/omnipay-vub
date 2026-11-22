# Changelog

## Unreleased

### Changed (BC break)

- Requires PHP 8.3 or newer (no PHP constraint before; Travis tested PHP 5.6 and 7.0).
- Requires `omnipay/common` ^3.2 (was ~3.0).
- Parameter getters and setters are typed: getters return `?string`, setters take `?string` and return `static`;
  `HmacSign::sign()` takes and returns `string`. Code that extends these classes must match the new signatures.
- `CompletePurchaseRequest` compares the `SIGN` query parameter with `hash_equals()` instead of `!=`;
  a missing `VS`, `RES` or `SIGN` query parameter no longer emits warnings and fails the signature check.
- The no-op `PurchaseRequest::initialize()` override is removed (the parent implementation is used).

### Added

- GitHub Actions CI (PHP 8.3 - 8.6, nightly 8.7, lowest dependencies), PHP-CS-Fixer (PER-CS), PHPStan level max
  with strict rules, `composer audit`, Dependabot.

### Removed

- Travis CI configuration, the empty PHPUnit configuration (the package has no tests yet),
  `omnipay/tests` and `squizlabs/php_codesniffer` dev dependencies.
