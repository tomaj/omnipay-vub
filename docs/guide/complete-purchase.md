# The return URL

After the payment VÚB redirects the customer to your `rurl` with the result in the query string:

```
https://shop.example/return?VS=2024000123&RES=OK&SIGN=6ADD04387C7A8787B51C1A5E48C546F66335BCF3DF18E93735A618470609C87F
```

| Field | Meaning |
|---|---|
| `VS` | the variable symbol you sent |
| `SS` | the specific symbol you sent; missing when the payment had none |
| `RES` | `OK` paid, `FAIL` rejected or cancelled, `TOUT` timed out |
| `SIGN` | uppercase hex HMAC-SHA256 of `VS + SS + RES` with the shared secret |

`completePurchase()` turns that into a verified answer for one order.

<<< @/../examples/return-url/run.php{php}

<<< @/../examples/return-url/expected-output.txt{text}

## The checks

`$gateway->completePurchase(['vs' => ..., 'ss' => ...])->send()` runs these checks in this order. The first one that fails throws Omnipay's `InvalidRequestException` with the message in the table:

| # | Check | Message |
|---|---|---|
| 1 | `vs` was passed and is not empty | `The vs parameter is required` |
| 2 | `SIGN` equals the HMAC-SHA256 of `VS + SS + RES` (compared with `hash_equals()`) | `incorect signature` (sic) |
| 3 | the returned `VS` is exactly the expected `vs` | `Variable symbol mismatch` |
| 4 | the returned `SS` is exactly the expected `ss` (`''` when `ss` is `null`) | `Specific symbol mismatch` |

A missing query parameter (or one that is not a string, like `RES[]=OK`) is read as `''`, so a request without `SIGN` fails check 2. The signature check runs before the symbol checks: a return with a wrong signature always reports `incorect signature`.

## The response

When all checks pass, `send()` returns a `CompletePurchaseResponse`:

| Method | Returns |
|---|---|
| `isSuccessful()` | `true` when `RES` is `OK` (`CompletePurchaseResponse::SUCCESS`) |
| `getRes()` | `'OK'`, `'FAIL'` or `'TOUT'` (whatever the bank signed) |
| `getVs()` | the returned, verified variable symbol |
| `getSs()` | the returned, verified specific symbol; `''` when there is none |
| `getData()` | `['RES' => ..., 'VS' => ..., 'SS' => ...]` |
| `isRedirect()`, `isCancelled()` | `false` (Omnipay defaults) |
| `getMessage()`, `getCode()`, `getTransactionReference()` | `null` (Omnipay defaults) |

## Handling the result

- **Exception**: the request was not produced by the bank for this order: forged, edited, or the genuine return of another order. Do not change the order. Log it, and show the customer the order page with its real state.
- **`isSuccessful()`**: mark the order paid, once. The amount is the one stored with the order: the bank does not sign the amount.
- **`FAIL` or `TOUT`**: the customer did not pay. Offer to pay again. Whether a new attempt keeps the variable symbol is up to your accounting; the gateway accepts either.

## Which order is it?

Load the order either from the customer's session or by the returned `VS` (`$request->query->get('VS')`), then pass **its** variable symbol to `completePurchase()`. Both are safe: the `vs` check stops a genuine success return of one order (for example a cheaper one) from completing another.

```php
$vs = $_GET['VS'] ?? '';
$order = $orders[$vs] ?? null;          // your lookup; unknown VS: show a 404 page
$response = $gateway->completePurchase(['vs' => $order['vs'], 'ss' => $order['ss']])->send();
echo $response->isSuccessful() ? "paid\n" : "not paid\n";
```

<!-- snippet: output -->
```text
paid
```

## Pass the right HTTP request

The query string is read from the Symfony request the gateway was created with, not from `$_GET`. Plain PHP works out of the box (`Request::createFromGlobals()`); in frameworks and long-running servers pass the current request: `new Gateway(null, $request)`, see [Configuration](./configuration#the-http-request-in-frameworks).

## Specific symbol

If you sent `ss` in `purchase()`, pass the same `ss` here. If you sent none, pass none: then a return that carries an `SS` is rejected. See the [Specific symbol example](../examples/specific-symbol).
