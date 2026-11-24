# Configuration

Parameters are set on the gateway, where they become the defaults of every request, or passed in the array of `purchase([...])` / `completePurchase([...])`, which overrides the gateway values for that request. Every parameter also has a typed setter on the request (`setVs()`, `setRurl()`, ...).

```php
use Omnipay\Eplatby\Gateway;

$gateway = new Gateway();
$gateway->setMid('12345678');
$gateway->setSharedSecret(getenv('VUB_SHARED_SECRET') ?: str_repeat('1', 64));
$gateway->setTestMode(false);

// the same with initialize(), for example from a configuration array
$gateway->initialize([
    'mid' => '12345678',
    'sharedSecret' => getenv('VUB_SHARED_SECRET') ?: str_repeat('1', 64),
    'testMode' => false,
]);

var_dump($gateway->getMid(), $gateway->getTestMode());
```

<!-- snippet: output -->
```text
string(8) "12345678"
bool(false)
```

::: tip initialize() resets
Like in every Omnipay gateway, `initialize()` first resets all parameters to their defaults and then applies the array.
:::

## Gateway parameters

| Parameter | Setter / getter | Type | Default | Description |
|---|---|---|---|---|
| `mid` | `setMid()` / `getMid()` | `?string` | `''` | Merchant ID assigned by VÚB. Sent as `MID` and signed. |
| `sharedSecret` | `setSharedSecret()` / `getSharedSecret()` | `?string` | `''` | The HMAC-SHA256 key from VÚB. Signs the request and verifies the return. Never sent anywhere. |
| `testMode` | `setTestMode()` / `getTestMode()` | `bool` | not set (off) | `true` posts the form to the payment simulator instead of VÚB. See [Test mode](./test-mode). |
| `testHost` | `setTestHost()` / `getTestHost()` | `?string` | `''` (means `https://platby.tomaj.sk`) | Scheme and host of the simulator used in test mode, without a trailing slash, for example `http://127.0.0.1:8080`. |

::: warning Empty values are not rejected
The gateway's defaults for `mid` and `sharedSecret` are empty strings, and Omnipay's required-parameter check only rejects missing (`null`) values. A gateway without `setMid()` or `setSharedSecret()` therefore signs with an empty MID or key instead of throwing. Check your configuration when the application starts, for example with `getenv('VUB_SHARED_SECRET') ?: throw new RuntimeException(...)`.
:::

## `purchase()` parameters

| Parameter | Sent as | Signed | Required | Description |
|---|---|---|---|---|
| `amount` | `AMT` | yes | by the bank | Amount in euro. Omnipay formats it with two decimals: `'10'`, `10` and `'10.00'` are all sent as `10.00`; more than two decimals throws `InvalidRequestException('Amount precision is too high for currency.')`, a negative amount `'A negative amount is not allowed.'`. `purchase()` does not check that it is set. |
| `vs` | `VS` | yes | yes | Variable symbol: identifies the order, digits only, up to 10. Use a value unique per order. |
| `ss` | `SS` | yes | no | Specific symbol, digits only, up to 10. If you send one, pass the same value to `completePurchase()`. |
| `cs` | `CS` | yes | no | Constant symbol, up to 4 digits (for example `0308`). |
| `rurl` | `RURL` | yes | yes | Return URL: where the bank sends the customer back with the result. |
| `rem` | `REM` | no | no | E-mail address for a payment notification from the bank. |
| `rsms` | `RSMS` | no | no | Mobile phone number for an SMS notification from the bank. |
| `mid`, `sharedSecret`, `testMode`, `testHost` | | | | Inherited from the gateway, can be overridden per request. |

`purchase()` throws `InvalidRequestException('The vs parameter is required')` (or `rurl`, `mid`, `sharedSecret`) when one of them is `null`. VÚB rejects a constant or specific symbol made only of zeros (`0`, `0000`) in production; the simulator accepts it, so leave the parameter out instead of sending zeros.

The currency is not a parameter: e-platby pays in euro. Omnipay's generic request parameters (`currency`, `description`, `transactionId`, `returnUrl`, ...) are accepted but not sent to the bank; use `rurl` for the return URL.

## `completePurchase()` parameters

| Parameter | Type | Required | Description |
|---|---|---|---|
| `vs` | `?string` | yes | The variable symbol of the order you are completing. The returned `VS` must be exactly this value (`'0123'` is not `'123'`). |
| `ss` | `?string` | when you sent one | The specific symbol you sent in `purchase()`. The returned `SS` must be exactly this value; `null` expects no `SS`. |
| `sharedSecret` | `?string` | yes | Usually inherited from the gateway. |

The returned `VS`, `SS`, `RES` and `SIGN` are read from the query string of the HTTP request the gateway was created with.

## The HTTP request in frameworks

Omnipay gives the gateway a Symfony `Request`, `Request::createFromGlobals()` by default, which is right for plain PHP. In a framework, or a long-running server (RoadRunner, FrankenPHP worker mode, Swoole), create the gateway per request with the current request:

```php
use Omnipay\Eplatby\Gateway;
use Symfony\Component\HttpFoundation\Request;

$request = Request::create('https://shop.example/return', 'GET', ['VS' => '2024000123', 'RES' => 'OK', 'SIGN' => '...']);

$gateway = new Gateway(null, $request);                 // null: Omnipay discovers the HTTP client
// or: Omnipay::create('Eplatby', null, $request);

echo $gateway->getShortName() . "\n";
```

<!-- snippet: output -->
```text
Eplatby
```

Laravel's `Illuminate\Http\Request` extends the Symfony request, so you can pass it directly; with a PSR-7 request, convert it with `symfony/psr-http-message-bridge`.
