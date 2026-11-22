# FAQ

## Why does `completePurchase()` throw "The vs parameter is required"?

Since 4.0 you have to pass the variable symbol of the order you are completing: `completePurchase(['vs' => $order->vs])`. It stops a valid return of one order from completing another. See [UPGRADE-4.0.md](https://github.com/tomaj/omnipay-vub/blob/master/UPGRADE-4.0.md).

## How do I check the paid amount?

You cannot from the return: VÚB does not send or sign it. Use the amount stored with the order whose variable symbol you passed to `completePurchase()`, and use a variable symbol that is unique per order. Reconcile with your bank statement for accounting.

## "incorect signature" on every return

- the `sharedSecret` differs from the one used to sign (test mode uses the simulator's key, production the key from VÚB);
- the return parameters were changed or URL-decoded twice by your router;
- your framework does not populate `$_GET`: pass the current request to the gateway (`new Gateway(null, $request)`).

## "Specific symbol mismatch"

You sent `ss` in `purchase()` but not in `completePurchase()` (or the other way round). Pass the same value to both.

## VÚB rejects the payment with symbols full of zeros

VÚB production does not accept a constant symbol or specific symbol consisting of zeros. Leave the parameter out instead.

## Can I use it without Omnipay's HTTP client?

The gateway makes no HTTP requests, but Omnipay's constructor discovers a PSR-18 client. Install one (`php-http/guzzle7-adapter`) or pass your own `Omnipay\Common\Http\ClientInterface` to the constructor.

## Which currencies are supported?

e-platby pays in euro; the request has no currency field.
