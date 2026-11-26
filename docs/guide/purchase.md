# Starting a payment

`purchase()` builds the signed payment request. Nothing is sent from your server: you hand the customer's browser a form that posts itself to the bank.

<<< @/../examples/purchase-form/run.php{php}

Output (the [Payment form example](../examples/purchase-form)):

<<< @/../examples/purchase-form/expected-output.txt{text}

## The request

`$gateway->purchase(array $parameters)` returns a `PurchaseRequest` with the gateway's parameters plus yours ([all parameters](./configuration#purchase-parameters)). `send()` validates the parameters, adds the signature and returns a `PurchaseResponse`. It throws:

| Exception | When |
|---|---|
| `InvalidRequestException('The sharedSecret parameter is required')`, `... mid ...`, `... vs ...`, `... rurl ...`, `... amount ...` | the parameter is `null` (not set and no gateway default); for `sharedSecret` also an empty or whitespace-only value, including the gateway's default `''` |
| `InvalidRequestException('The vs parameter must be up to 10 digits')` | `vs` is not 1 to 10 digits (`0`-`9` only, no spaces or signs) |
| `InvalidRequestException('The cs parameter must be up to 4 digits')` | `cs` is set (not `null` or `''`) but is not 1 to 4 digits |
| `InvalidRequestException('The ss parameter must be up to 10 digits')` | `ss` is set but is not 1 to 10 digits |
| `InvalidRequestException('The vs parameter must be exactly 10 digits when ss is set')` | `ss` is set and `vs` is not exactly 10 digits ([Choosing the variable symbol](#choosing-the-variable-symbol)) |
| `InvalidRequestException('The rurl parameter must be an absolute https:// URL')` | `rurl` is not an absolute `https://` URL with a host (relative, another scheme, or containing whitespace or control characters); in test mode `http://` is accepted too and the message is `... must be an absolute https:// or http:// URL` |
| `InvalidRequestException('Amount precision is too high for currency.')` | `amount` has more than two decimals |
| `InvalidRequestException('A negative amount is not allowed.')` | `amount` is negative |

All of them are thrown by `getData()`, before anything is signed, so a malformed payment never reaches the bank's error page. Accepted formats:

| Parameter | Format |
|---|---|
| `vs` | 1 to 10 digits; exactly 10 when `ss` is set |
| `ss` | optional; 1 to 10 digits |
| `cs` | optional; 1 to 4 digits |
| `rurl` | absolute `https://` URL with a host, no whitespace or control characters; `http://` only in test mode |

`getData()` returns the fields without the signature, in this order: `MID`, `AMT`, `VS`, `CS`, `RURL`, `SS`, `REM`, `RSMS` (`null` for an empty optional field). `sendData($data)` adds `SIGN`.

## The response

| Method | Returns |
|---|---|
| `isSuccessful()` | `false`: the payment has not happened yet |
| `isRedirect()` | `true` |
| `getRedirectMethod()` | `'POST'` |
| `getRedirectUrl()` | `https://ib.vub.sk/e-platbyeuro.aspx`, or the simulator in [test mode](./test-mode) |
| `getRedirectData()` | the fields of `getData()` plus `SIGN` |
| `redirect()` | sends Omnipay's auto-submitting HTML page (status 200) to the browser |
| `getRedirectResponse()` | the same page as a Symfony `Response`, for frameworks that return a response object |

`redirect()` writes the page with `echo`: call it in the controller instead of rendering a template, and do not send other output. In frameworks, return `getRedirectResponse()`:

```php
$response = $gateway->purchase(['amount' => '10.00', 'vs' => '2024000123', 'rurl' => 'https://shop.example/return'])->send();

$httpResponse = $response->getRedirectResponse();   // Symfony\Component\HttpFoundation\Response
echo $httpResponse->getStatusCode() . ', ' . substr_count((string) $httpResponse->getContent(), 'type="hidden"') . " hidden fields\n";
```

<!-- snippet: output -->
```text
200, 9 hidden fields
```

Omnipay's page posts every field, including empty optional ones (`SS`, `RSMS`, ... as empty strings). It has a "Continue" button for browsers without JavaScript.

## Your own form

To show the customer a summary and a "Pay with VÚB" button instead of the auto-submitting page, render the form from `getRedirectUrl()` and `getRedirectData()`. Escape every value, and leave out empty fields if you like: empty fields do not change the signature.

<<< @/../examples/custom-form/run.php{php}

The rendered form is on the [Your own pay button](../examples/custom-form) example page.

## Choosing the variable symbol

The variable symbol is how the return finds its order, and the bank's signature binds the result to it. Make it:

- **unique per order** (and per payment attempt, if your accounting needs that), never reused;
- **digits only, up to 10**, as the bank requires;
- **exactly 10 digits** if you also use specific symbols, for example the order ID padded with zeros: `str_pad((string) $id, 10, '0', STR_PAD_LEFT)`. The bank signs `VS + SS` without a separator; with a full-length variable symbol a signed `VS + SS` can only be split one way. `purchase()` and `completePurchase()` throw `InvalidRequestException('The vs parameter must be exactly 10 digits when ss is set')` otherwise ([Security](./security#variable-and-specific-symbol)). Without a specific symbol any length up to 10 works, but a fixed length is still the safer choice.

## Signing a string yourself

`PurchaseRequest::generateSignature(string $data)` returns the uppercase HMAC-SHA256 of any string with the request's `sharedSecret`, the same as `new HmacSign()->sign($data, $secret)` ([Signatures](./signatures)).
