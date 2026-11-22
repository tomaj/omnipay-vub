# Getting started

omnipay-vub is a gateway for [Omnipay](https://omnipay.thephpleague.com/) that pays through [VÚB e-platby](https://www.vub.sk/en/firmy-a-podnikatelia/platby/eplatby.html), the payment button of VÚB banka (Slovakia). The customer is redirected to VÚB internet banking, confirms the payment there and comes back to your return URL with the result.

## Requirements

- PHP 8.4 or newer (tested on 8.4, 8.5 and 8.6) with `ext-hash`
- `omnipay/common` ^3.2
- an HTTP client for Omnipay: any PSR-18 client found by `php-http/discovery`, for example `php-http/guzzle7-adapter`. The gateway itself makes no HTTP calls, but Omnipay creates a client when the gateway is constructed.
- a merchant contract with VÚB for e-platby: your **MID** and the **shared secret** (HMAC key)

## Installation

```bash
composer require tomaj/omnipay-vub php-http/guzzle7-adapter
```

## The payment flow

1. Your checkout creates a `purchase()` request with the amount, the order's variable symbol and your return URL, and sends the customer to VÚB with an auto-submitting POST form (`redirect()`).
2. The customer pays (or cancels) in VÚB internet banking.
3. VÚB redirects the customer to your return URL (`RURL`) with `VS`, `SS`, `RES` and `SIGN` in the query string.
4. Your return URL calls `completePurchase()` with the variable symbol of the order. It verifies the signature and the symbols, and `isSuccessful()` tells you whether the payment went through (`RES=OK`).

## First payment

The checkout page:

<<< @/../examples/purchase.php

The return URL:

<<< @/../examples/return.php

Both files are in [`examples/`](https://github.com/tomaj/omnipay-vub/tree/master/examples) and run in the test suite. With test mode on, the form goes to the [payment simulator](./test-mode), where you can choose a successful, failed or timed-out payment.

::: warning The amount is not signed
VÚB signs only the variable symbol, the specific symbol and the result of the return, not the amount. Always take the amount from your stored order, never from the request. See [Signatures](./signatures).
:::

## Next steps

- [Configuration](./configuration): every parameter
- [Operations](./operations): `purchase()` and `completePurchase()` in detail, error handling
- [Signatures](./signatures): the exact signed strings
- [Test mode](./test-mode): the simulator and VÚB's test environment
