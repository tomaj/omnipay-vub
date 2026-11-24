# FAQ

## Why does `completePurchase()` throw "The vs parameter is required"?

Since 4.0 you pass the variable symbol of the order you are completing: `completePurchase(['vs' => $order->vs])`. It stops a valid return of one order from completing another. See [Upgrade from 3.x](./upgrade).

## How do I check the paid amount?

You cannot from the return: VÚB neither sends nor signs it. Use the amount stored with the order whose variable symbol you passed to `completePurchase()`, and use a variable symbol that is unique per order. Reconcile with the bank statement for accounting.

## "incorect signature" on every return

- the `sharedSecret` differs from the one the return was signed with (test mode uses the simulator's key, production the key from VÚB);
- your router changes or decodes the query string twice;
- in a framework or a long-running server the gateway reads another request than the current one: pass it, `new Gateway(null, $request)`.

The message is spelled `incorect` in the code; match on the exception class, not the text.

## "Specific symbol mismatch"

You sent `ss` in `purchase()` but not in `completePurchase()`, or the other way round. Pass the same value to both.

## VÚB rejects the payment with symbols full of zeros

VÚB production does not accept a constant or specific symbol made only of zeros. Leave the parameter out instead.

## Is there a server-to-server notification?

No. e-platby only redirects the customer back. If the customer closes the browser after paying, your shop learns about the payment from the bank statement. `REM` and `RSMS` make the bank notify the customer, not you.

## Can I refund, capture or ask for the payment status?

No. e-platby has only the payment button: `purchase()` and `completePurchase()`. Refunds are bank transfers.

## Which currencies are supported?

Euro only; the request has no currency field.

## Can I use it without Omnipay's HTTP client?

The gateway makes no HTTP requests, but Omnipay's constructor discovers a PSR-18 client. Install one (`php-http/guzzle7-adapter`) or pass your own `Omnipay\Common\Http\ClientInterface` to the constructor.

## How do I use it in Laravel, Symfony or Nette?

Create the gateway per request and pass the current HTTP request (a Symfony `Request`; Laravel's request extends it). Call `redirect()` or return `getRedirectResponse()` in the checkout action, `completePurchase()` in the return action. See [Configuration](./guide/configuration#the-http-request-in-frameworks).

## Why are the classes `final`?

So that the signature and verification code cannot be changed by accident in a subclass. Wrap the gateway in your own service instead of extending it; see [Upgrade from 3.x](./upgrade#final-classes).
