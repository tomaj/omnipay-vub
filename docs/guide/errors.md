# Error handling

The gateway reports problems with exceptions; a payment that did not go through is not an exception but a response with `isSuccessful() === false`.

## What can be thrown

| Exception | Thrown by | When |
|---|---|---|
| `Omnipay\Common\Exception\InvalidRequestException` | `purchase()->send()`, `getData()` | a required parameter is `null` (`The vs parameter is required`, `... rurl ...`, `... amount ...`, `... mid ...`, `... sharedSecret ...`), an invalid amount (`Amount precision is too high for currency.`, `A negative amount is not allowed.`), `sendData()` called with something else than an array (`Purchase data must be an array`) |
| `Omnipay\Common\Exception\InvalidRequestException` | `completePurchase()->send()`, `getData()` | `The vs parameter is required`, `incorect signature`, `Variable symbol mismatch`, `Specific symbol mismatch` ([the checks](./complete-purchase#the-checks)) |
| `Omnipay\Eplatby\Exception\LogicException` | `PurchaseResponse::getRedirectUrl()` | the response was created for another request than a `PurchaseRequest` (a programming error) |
| `Omnipay\Common\Exception\RuntimeException` | Omnipay, `initialize()` and setters | the request was changed after `send()` (`Request cannot be modified after it has been sent!`) |

Every exception the package defines implements `Omnipay\Eplatby\Exception\EplatbyException`, which extends Omnipay's `OmnipayException`. `InvalidRequestException` and Omnipay's `RuntimeException` implement `OmnipayException` too, so one `catch` handles everything the gateway throws:

```php
use Omnipay\Common\Exception\OmnipayException;

try {
    $response = $gateway->completePurchase(['vs' => '2024000999'])->send();
} catch (OmnipayException $e) {
    echo get_class($e) . ': ' . $e->getMessage() . "\n";
}
```

<!-- snippet: output -->
```text
Omnipay\Common\Exception\InvalidRequestException: Variable symbol mismatch
```

## On the return URL

Every `InvalidRequestException` from `completePurchase()` means the same thing for the order: **this request does not prove a payment of this order**. Do not change the order, answer with an error page (400 is fine), and log the message together with the query string; it helps to tell a misconfiguration from an attack:

| Message | Usual cause |
|---|---|
| `incorect signature` on every return | wrong `sharedSecret` (the simulator key in production or the other way round), or a router that changes or decodes the query string twice |
| `incorect signature` now and then | somebody edits the URL, or opens the return URL without parameters |
| `Variable symbol mismatch` | the return of another order, or the order loaded from the session differs from the one the customer paid in another tab |
| `Specific symbol mismatch` | `ss` sent in `purchase()` but not passed to `completePurchase()`, or the other way round |
| `The vs parameter is required` | a bug in your return URL: no order loaded |

## In the checkout

Exceptions from `purchase()` are configuration or programming errors: a missing return URL, amount or variable symbol, an amount with three decimals. Let them reach your error handler, or validate the order before you call the gateway.
