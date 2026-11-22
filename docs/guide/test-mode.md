# Test mode

With `testMode` on, `purchase()` posts the form to a payment simulator instead of VÚB:

| Mode | Endpoint |
|---|---|
| production (`testMode` false) | `https://ib.vub.sk/e-platbyeuro.aspx` |
| test mode | `https://platby.tomaj.sk/payment/eplatby-hmac` |
| test mode with `testHost` | `<testHost>/payment/eplatby-hmac` |

```php
$gateway->setTestMode(true);
$gateway->setTestHost('http://localhost:8080'); // optional: your own simulator
```

## Payment simulator

[tomaj/payment-simulator](https://github.com/tomaj/payment-simulator) implements the e-platby HMAC endpoint (and other Slovak bank buttons). It signs with the shared secret `1111111111111111111111111111111111111111111111111111111111111111` (64 × `1`) and any MID, shows whether your request signature matches, and offers links back to your `RURL` with a signed `OK`, `FAIL` or `TOUT` result. `https://platby.tomaj.sk` is a public instance; run your own with Docker for CI or private data.

Use the simulator's secret only in test mode, and never enable test mode in production: anyone could then produce a valid return with the public key.

## VÚB test environment

VÚB also runs a test environment for merchants (`https://nib.vub.sk/nepay/merchant`). It needs test credentials from VÚB and a different path than the simulator, so `testHost` cannot point at it; ask VÚB (e_platby@vub.sk) for access and the current URL.

::: warning Specific symbol in the simulator
The simulator still signs the specific symbol in another position ([payment-simulator#26](https://github.com/tomaj/payment-simulator/issues/26)), so it reports a wrong signature for payments with `ss`. Payments without a specific symbol work.
:::
