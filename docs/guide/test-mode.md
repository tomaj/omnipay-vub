# Test mode

With `testMode` on, `purchase()` posts the form to a payment simulator instead of VÚB. Nothing else changes: the signatures, the return and `completePurchase()` work the same.

| Mode | `getRedirectUrl()` |
|---|---|
| production (`testMode` not set or `false`) | `https://ib.vub.sk/e-platbyeuro.aspx` |
| test mode | `https://platby.tomaj.sk/payment/eplatby-hmac` |
| test mode with `testHost` | `<testHost>/payment/eplatby-hmac` |

```php
$gateway->setTestMode(true);
echo $gateway->purchase(['vs' => '1', 'rurl' => 'https://shop.example/return'])->getEndpoint() . "\n";

$gateway->setTestHost('http://127.0.0.1:8080');   // your own simulator
echo $gateway->purchase(['vs' => '1', 'rurl' => 'https://shop.example/return'])->getEndpoint() . "\n";

$gateway->setTestMode(false);
echo $gateway->purchase(['vs' => '1', 'rurl' => 'https://shop.example/return'])->getEndpoint() . "\n";
```

<!-- snippet: output -->
```text
https://platby.tomaj.sk/payment/eplatby-hmac
http://127.0.0.1:8080/payment/eplatby-hmac
https://ib.vub.sk/e-platbyeuro.aspx
```

`testHost` is used only in test mode; an empty `testHost` means `https://platby.tomaj.sk`.

## Payment simulator

[tomaj/payment-simulator](https://github.com/tomaj/payment-simulator) implements the e-platby HMAC endpoint (and the other Slovak bank buttons). It signs with the shared secret `1111111111111111111111111111111111111111111111111111111111111111` (64 × `1`) and any MID, shows whether your request signature matches, and offers links back to your `RURL` with a signed `OK`, `FAIL` or `TOUT` result. `https://platby.tomaj.sk` is a public instance; run your own with Docker for CI or private data and point `testHost` at it.

::: warning Specific symbol in the simulator
The simulator still signs the specific symbol in another position ([payment-simulator#26](https://github.com/tomaj/payment-simulator/issues/26)), so it reports a wrong signature for payments with `ss`. Payments without a specific symbol work.
:::

## A fake bank on localhost

For automated tests you do not need the simulator: e-platby is two signed redirects, which a few lines of PHP can play. The [Full redirect flow example](../examples/full-flow) runs a shop and a fake bank with `php -S` and drives them like a browser; [Testing your integration](./testing) shows unit tests without any server.

## VÚB test environment

VÚB also runs a test environment for merchants (`https://nib.vub.sk/nepay/merchant`). It needs test credentials from VÚB and has another path than the simulator, so `testHost` cannot point at it; ask VÚB (e_platby@vub.sk) for access and the current URL.

## Never in production

The simulator's key is public: a shop that verifies returns with it accepts an `OK` return that anyone can sign. Use the simulator key only together with test mode, and load the key and the mode from the environment, so a production deploy cannot pick them up by accident.
