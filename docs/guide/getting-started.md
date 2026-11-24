# Getting started

omnipay-vub is a gateway for [Omnipay](https://omnipay.thephpleague.com/), the PHP payment processing library. It pays through [VÚB e-platby](https://www.vub.sk/en/firmy-a-podnikatelia/platby/eplatby.html), the payment button of VÚB banka (Slovakia): the customer is sent to VÚB internet banking, confirms the payment there and comes back to your return URL with a signed result.

## Requirements

- PHP 8.4 or newer (tested on 8.4, 8.5 and 8.6) with `ext-hash`
- [`omnipay/common`](https://packagist.org/packages/omnipay/common) ^3.2 (installed with the package)
- an HTTP client for Omnipay: any PSR-18 client that `php-http/discovery` finds, for example `php-http/guzzle7-adapter`. The gateway itself makes no HTTP calls, but Omnipay creates a client when the gateway is constructed.
- a contract with VÚB for e-platby, which gives you your **MID** (merchant ID) and the **shared secret** (the HMAC key). Until you have them, use [test mode](./test-mode).

## Installation

```bash
composer require tomaj/omnipay-vub php-http/guzzle7-adapter
```

## Create the gateway

Create it with Omnipay's factory (`Eplatby` is the gateway's short name) or with `new`:

```php
use Omnipay\Eplatby\Gateway;
use Omnipay\Omnipay;

$gateway = Omnipay::create('Eplatby');   // or: new Gateway()
$gateway->setMid('12345678');
$gateway->setSharedSecret(getenv('VUB_SHARED_SECRET') ?: str_repeat('1', 64));
$gateway->setTestMode(true);              // the payment simulator until you go live

echo $gateway->getName() . "\n";
```

<!-- snippet: output -->
```text
VUB ePlatby Gateway
```

Keep the shared secret in the environment or a secret store, never in the code. Every parameter is described in [Configuration](./configuration).

## 1. Send the customer to the bank

The checkout creates a `purchase()` request with the amount, the order's variable symbol and your return URL. `redirect()` prints an HTML page whose form posts itself to VÚB:

```php
$response = $gateway->purchase([
    'amount' => '10.00',
    'vs' => '2024000123',
    'rurl' => 'https://shop.example/return',
])->send();

echo $response->getRedirectMethod() . ' ' . $response->getRedirectUrl() . "\n";
echo 'SIGN=' . $response->getRedirectData()['SIGN'] . "\n";
// in the checkout controller: $response->redirect();
```

<!-- snippet: output -->
```text
POST https://platby.tomaj.sk/payment/eplatby-hmac
SIGN=B06619E24483E33B56626C8FBAEFCB142A16A535DA645D35E819DF6C4A6CF26C
```

Store the order with its amount and variable symbol before you redirect: the return URL needs them. See [Starting a payment](./purchase).

## 2. Verify the return

VÚB sends the customer to `rurl` with `VS`, `SS`, `RES` and `SIGN` in the query string. Call `completePurchase()` with the variable symbol of the order you expect:

```php
use Omnipay\Common\Exception\InvalidRequestException;

try {
    $response = $gateway->completePurchase(['vs' => '2024000123'])->send();
} catch (InvalidRequestException $e) {
    // forged or tampered return, or the return of another order: do not touch the order
    exit('Invalid return: ' . $e->getMessage());
}

if ($response->isSuccessful()) {
    echo "Paid, VS {$response->getVs()}\n";   // mark the order paid, with the amount stored on your side
} else {
    echo "Not paid: {$response->getRes()}\n";  // FAIL or TOUT: offer to pay again
}
```

<!-- snippet: output -->
```text
Paid, VS 2024000123
```

::: warning The amount is not signed
VÚB signs only the variable symbol, the specific symbol and the result of the return. Take the amount from your stored order, never from the request. See [Security](./security).
:::

## Next steps

- [The payment flow](./payment-flow): what happens between the shop, the browser and the bank
- [Configuration](./configuration): every parameter
- [Examples](../examples/): runnable recipes, including a shop and a fake bank on localhost
- [Test mode](./test-mode): the payment simulator
