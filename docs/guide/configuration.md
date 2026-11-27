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
| `sharedSecret` | `setSharedSecret()` / `getSharedSecret()` | `?string` | `''` | The HMAC-SHA256 key from VÚB. Signs the request and verifies the return. Never sent anywhere. Required: `null`, `''` (the default) or only whitespace throws `InvalidRequestException('The sharedSecret parameter is required')` in `purchase()` and `completePurchase()`. |
| `testMode` | `setTestMode()` / `getTestMode()` | `bool` | not set (off) | `true` posts the form to the payment simulator instead of VÚB. See [Test mode](./test-mode). |
| `testHost` | `setTestHost()` / `getTestHost()` | `?string` | `''` (means `https://platby.tomaj.sk`) | Scheme and host of the simulator used in test mode, without a trailing slash, for example `http://127.0.0.1:8080`. |

::: warning Check your configuration at startup
An empty or whitespace-only `sharedSecret` (for example from a missing environment variable) is rejected since 4.0: every `purchase()` and `completePurchase()` throws. The `mid` default is still an empty string that Omnipay's required-parameter check accepts. Check both when the application starts, for example with `getenv('VUB_SHARED_SECRET') ?: throw new RuntimeException(...)`, so a broken deployment fails at once instead of at the first payment.
:::

## `purchase()` parameters

| Parameter | Sent as | Signed | Required | Description |
|---|---|---|---|---|
| `amount` | `AMT` | yes | yes | Amount in euro. Omnipay formats it with two decimals: `'10'`, `10` and `'10.00'` are all sent as `10.00`; more than two decimals throws `InvalidRequestException('Amount precision is too high for currency.')`, a negative amount `'A negative amount is not allowed.'`. |
| `vs` | `VS` | yes | yes | Variable symbol: identifies the order, digits only, up to 10 (checked); **exactly 10** when `ss` is set (pad with zeros). Use a value unique per order. |
| `ss` | `SS` | yes | no | Specific symbol, digits only, up to 10 (checked). Requires a 10-digit `vs`. If you send one, pass the same value to `completePurchase()`. |
| `cs` | `CS` | yes | no | Constant symbol, digits only, up to 4 (checked; for example `0308`). |
| `rurl` | `RURL` | yes | yes | Return URL: where the bank sends the customer back with the result. An absolute `https://` URL (checked); `http://` is accepted in test mode only. |
| `rem` | `REM` | no | no | E-mail address for a payment notification from the bank. |
| `rsms` | `RSMS` | no | no | Mobile phone number for an SMS notification from the bank. |
| `mid`, `sharedSecret`, `testMode`, `testHost` | | | | Inherited from the gateway, can be overridden per request. |

`purchase()` throws `InvalidRequestException('The vs parameter is required')` (or `rurl`, `amount`, `mid`, `sharedSecret`) when one of them is `null`, and for an empty `sharedSecret` too. A malformed value throws before anything is signed: `The vs parameter must be up to 10 digits`, `The cs parameter must be up to 4 digits` (with `cs` set also `The vs parameter must be exactly 10 digits when cs is set`), `The rurl parameter must be an absolute https:// URL` (`... https:// or http:// URL` in test mode), and with `ss` set `The ss parameter must be up to 10 digits` or `The vs parameter must be exactly 10 digits when ss is set`. VÚB rejects a constant or specific symbol made only of zeros (`0`, `0000`) in production; the simulator accepts it, so leave the parameter out instead of sending zeros.

The currency is not a parameter: e-platby pays in euro. Omnipay's generic request parameters (`currency`, `description`, `transactionId`, `returnUrl`, ...) are accepted but not sent to the bank; use `rurl` for the return URL.

## `completePurchase()` parameters

| Parameter | Type | Required | Description |
|---|---|---|---|
| `vs` | `?string` | yes | The variable symbol of the order you are completing. The returned `VS` must be exactly this value (`'0123'` is not `'123'`). Exactly 10 digits when `ss` is set. |
| `ss` | `?string` | when you sent one | The specific symbol you sent in `purchase()`. The returned `SS` must be exactly this value; `null` expects no `SS`. |
| `sharedSecret` | `?string` | yes | Usually inherited from the gateway. Empty or whitespace-only throws. |

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
