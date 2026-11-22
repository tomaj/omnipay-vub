# Operations

The gateway supports the two operations of VÚB e-platby: starting a payment and completing it on the return URL. Refunds, captures and payment status queries are not part of e-platby.

## `purchase()`

Builds the signed payment form. Nothing is sent from your server: the customer's browser posts the form to the bank.

<<< @/../examples/purchase.php

`send()` returns a `PurchaseResponse`:

| Method | Value |
|---|---|
| `isSuccessful()` | `false`: the payment is not finished yet |
| `isRedirect()` | `true` |
| `getRedirectMethod()` | `'POST'` |
| `getRedirectUrl()` | `https://ib.vub.sk/e-platbyeuro.aspx`, or the simulator in test mode |
| `getRedirectData()` | `MID`, `AMT`, `VS`, `CS`, `RURL`, `SS`, `REM`, `RSMS`, `SIGN` |
| `redirect()` | prints an auto-submitting HTML form (Omnipay) |

To render the form yourself (for example in a template), use `getRedirectUrl()` and `getRedirectData()`:

```php
$response = $gateway->purchase([
    'amount' => '10.00',
    'vs' => '2024000123',
    'rurl' => 'https://shop.example/return.php',
])->send();

$action = $response->getRedirectUrl();
$fields = array_filter($response->getRedirectData(), fn (?string $value): bool => $value !== null);
// <form method="post" action="$action"> with a hidden input per field
```

`getData()` throws Omnipay's `InvalidRequestException` (`The mid parameter is required`, ...) when `sharedSecret`, `mid`, `vs` or `rurl` is missing.

## `completePurchase()`

Verifies the return request of the bank. Call it on your return URL with the variable symbol (and the specific symbol, if you sent one) of the order:

<<< @/../examples/return.php

`send()` checks, in this order, and throws Omnipay's `InvalidRequestException` when a check fails:

| Check | Message |
|---|---|
| `vs` was passed and is not empty | `The vs parameter is required` |
| `SIGN` is the HMAC-SHA256 of `VS + SS + RES` (constant-time comparison) | `incorect signature` |
| the returned `VS` is exactly the expected `vs` | `Variable symbol mismatch` |
| the returned `SS` is exactly the expected `ss` (`''` when not set) | `Specific symbol mismatch` |

It returns a `CompletePurchaseResponse`:

| Method | Value |
|---|---|
| `isSuccessful()` | `true` when `RES` is `OK` |
| `getRes()` | `OK` (paid), `FAIL` (rejected or cancelled), `TOUT` (timed out) |
| `getVs()` | the returned (and verified) variable symbol |
| `getSs()` | the returned (and verified) specific symbol, `''` when there is none |
| `getData()` | `['RES' => ..., 'VS' => ..., 'SS' => ...]` |

### Handling the result

- **Exception**: the request was not produced by the bank for this order (forged, tampered, or the return of another order). Do not change the order; log the attempt.
- **`isSuccessful()`**: mark the order paid. The amount is the one stored with the order: the bank does not sign the amount, so the return URL cannot tell you how much was paid.
- **`FAIL` / `TOUT`**: the customer did not pay. Let them try again (with the same or a new variable symbol, depending on your accounting).

The customer may open the return URL more than once (reload, back button). Make marking the order paid idempotent.

### Which order?

Load the order either by the returned `VS` (as in the example) or from the customer's session, and always pass its variable symbol to `completePurchase()`. If you load it from the session, the `vs` check stops a genuine success return of another order (for example a cheaper one) from completing this one.
