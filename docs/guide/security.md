# Security

omnipay-vub signs your payment requests and verifies the bank's returns. This page lists what that protects, what it does not, and what is left to your shop. To report a vulnerability, see the [security policy](https://github.com/tomaj/omnipay-vub/security/policy): privately, not in a public issue.

## The shared secret

- The `sharedSecret` signs requests and verifies returns. Anyone who has it can forge an `OK` return. Keep it in the environment or a secret store; do not commit, log or send it to the browser.
- An empty or whitespace-only secret is rejected: `purchase()` and `completePurchase()` throw `InvalidRequestException('The sharedSecret parameter is required')` instead of signing with an empty HMAC key, which anyone could reproduce. Still check the configuration when the application starts, so a missing secret fails the deployment, not the first payment.
- The simulator's key (64 × `1`) is public. Use it only with test mode.

### Stack traces

Error reports (Tracy, Sentry, Monolog, `getTraceAsString()`) show the arguments of every call on the stack. The parameters that take the secret are marked with `#[\SensitiveParameter]`, so PHP shows `Object(SensitiveParameterValue)` instead of the key: `setSharedSecret()` on `Gateway`, `PurchaseRequest` and `CompletePurchaseRequest`, and `HmacSign::sign()`.

This does not cover arrays: a secret passed as `initialize(['sharedSecret' => ...])` or `purchase(['sharedSecret' => ...])` is still printed with the array. Set it with `setSharedSecret()`, and turn off arguments in traces in production with `zend.exception_ignore_args=On` in `php.ini` (the default of `php.ini-production`).

## What `completePurchase()` guarantees

When it returns without an exception:

- the bank (or whoever holds the shared secret) produced this `VS`, `SS` and `RES`; the signature was compared in constant time (`hash_equals()`);
- the return is for the variable symbol you passed, and for the specific symbol you passed (or none).

## What it cannot guarantee

- **The amount.** VÚB does not send or sign the amount in the return. The amount is the one stored with the order whose variable symbol you passed. Never read an amount, price or order total from the request.
- **Freshness.** The return has no timestamp or nonce; the same URL verifies again and again. Make "mark as paid" idempotent, and do not trigger side effects (shipping, e-mails) twice.
- **That a return arrives at all.** The customer may close the browser after paying. Reconcile unpaid orders with the bank statement.

## Variable and specific symbol

The bank signs `VS + SS + RES` without a separator, so `VS=12345` with `SS=6` signs the same string as `VS=123456` without `SS`. The gateway closes this in two steps:

- `completePurchase()` compares both symbols with the ones of your order (`ss` `null` means "no `SS`"), so a return whose digits were moved between `VS` and `SS` does not complete that order;
- with a specific symbol, `purchase()` and `completePurchase()` require a variable symbol of exactly 10 digits, the maximum VÚB allows. A signed `VS + SS` of such an order can only be split one way, and it cannot equal the `VS` of an order without a specific symbol (at most 10 digits). Pad the order ID: `str_pad((string) $id, 10, '0', STR_PAD_LEFT)`.

Without specific symbols, nothing changes: every order expects an empty `SS`, so a return with one is rejected. Variable symbols of one fixed length (10 digits) are still recommended for every order.

## Test mode

Test mode only changes where the form is posted. Never use the simulator's public key in production, and keep `testMode` and the key in the same environment configuration.

## Transport

Serve the checkout and the return URL over HTTPS. The return carries no secret, but over plain HTTP anyone on the network could see and replay it.

## Reporting

Report vulnerabilities through [GitHub private vulnerability reporting](https://github.com/tomaj/omnipay-vub/security/advisories/new). The [security policy](https://github.com/tomaj/omnipay-vub/security/policy) lists the supported versions.
