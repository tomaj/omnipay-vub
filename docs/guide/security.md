# Security

omnipay-vub signs your payment requests and verifies the bank's returns. This page lists what that protects, what it does not, and what is left to your shop. To report a vulnerability, see the [security policy](https://github.com/tomaj/omnipay-vub/security/policy): privately, not in a public issue.

## The shared secret

- The `sharedSecret` signs requests and verifies returns. Anyone who has it can forge an `OK` return. Keep it in the environment or a secret store; do not commit, log or send it to the browser.
- The gateway does not check that the secret is set (see [Configuration](./configuration#gateway-parameters)): fail when the application starts if it is missing.
- The simulator's key (64 × `1`) is public. Use it only with test mode.

## What `completePurchase()` guarantees

When it returns without an exception:

- the bank (or whoever holds the shared secret) produced this `VS`, `SS` and `RES`; the signature was compared in constant time (`hash_equals()`);
- the return is for the variable symbol you passed, and for the specific symbol you passed (or none).

## What it cannot guarantee

- **The amount.** VÚB does not send or sign the amount in the return. The amount is the one stored with the order whose variable symbol you passed. Never read an amount, price or order total from the request.
- **Freshness.** The return has no timestamp or nonce; the same URL verifies again and again. Make "mark as paid" idempotent, and do not trigger side effects (shipping, e-mails) twice.
- **That a return arrives at all.** The customer may close the browser after paying. Reconcile unpaid orders with the bank statement.

## Variable and specific symbol

The bank signs `VS + SS + RES` without a separator, so `VS=12345` with `SS=6` signs the same string as `VS=123456` without `SS`. `completePurchase()` compares both symbols with the ones of your order, which stops a return whose digits were moved between `VS` and `SS` from completing that order. It cannot help when your shop has two orders whose symbols run together the same way (one with `VS=123456` and no `SS`, another with `VS=12345` and `SS=6`). Avoid that by construction:

- use variable symbols of **one fixed length**, for example the order ID padded to 10 digits, whenever you use specific symbols; or
- do not use specific symbols at all.

## Test mode

Test mode only changes where the form is posted. Never use the simulator's public key in production, and keep `testMode` and the key in the same environment configuration.

## Transport

Serve the checkout and the return URL over HTTPS. The return carries no secret, but over plain HTTP anyone on the network could see and replay it.

## Reporting

Report vulnerabilities through [GitHub private vulnerability reporting](https://github.com/tomaj/omnipay-vub/security/advisories/new). The [security policy](https://github.com/tomaj/omnipay-vub/security/policy) lists the supported versions.
