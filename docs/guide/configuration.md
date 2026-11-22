# Configuration

Parameters are set on the gateway (they become the defaults of every request) or passed to `purchase([...])` / `completePurchase([...])`, which override the gateway values. Each parameter also has a typed setter (`setMid()`, `setVs()`, ...).

```php
use Omnipay\Eplatby\Gateway;

$gateway = new Gateway();
$gateway->setMid('12345678');
$gateway->setSharedSecret($_ENV['VUB_SHARED_SECRET']);
$gateway->setTestMode(false);
```

## Gateway parameters

| Parameter | Setter | Description |
|---|---|---|
| `mid` | `setMid()` | Merchant ID assigned by VÚB. Sent as `MID`. Required for `purchase()`. |
| `sharedSecret` | `setSharedSecret()` | The HMAC-SHA256 key from VÚB. Signs the request and verifies the return. Never sent to the browser. Required. |
| `testMode` | `setTestMode()` | `true` sends payments to the payment simulator instead of `https://ib.vub.sk/e-platbyeuro.aspx`. Default `false`. See [Test mode](./test-mode). |
| `testHost` | `setTestHost()` | Scheme and host of the simulator used in test mode, without a trailing slash. Default `https://platby.tomaj.sk`. |

## `purchase()` parameters

| Parameter | Sent as | Signed | Description |
|---|---|---|---|
| `amount` | `AMT` | yes | Amount in EUR, formatted by Omnipay with two decimals (`'10.00'`). |
| `vs` | `VS` | yes | Variable symbol: identifies the order, up to 10 digits. Required. Use a value unique per order. |
| `ss` | `SS` | yes | Specific symbol, optional. If you send it, pass the same value to `completePurchase()`. |
| `cs` | `CS` | yes | Constant symbol, optional (for example `0308`). |
| `rurl` | `RURL` | yes | Return URL the bank redirects the customer to. Required. |
| `rem` | `REM` | no | E-mail address for the payment notification from the bank, optional. |
| `rsms` | `RSMS` | no | Phone number for an SMS notification from the bank, optional. |

VÚB rejects a constant or specific symbol made only of zeros (`0`, `0000`) in production; the simulator accepts them, so leave the parameter out instead of sending zeros.

Omnipay converts values passed in the array, so `['vs' => 2024000123]` works; the typed setters take `?string`.

## `completePurchase()` parameters

| Parameter | Description |
|---|---|
| `vs` | The variable symbol of the order you are completing. **Required.** The return's `VS` must be exactly this value. |
| `ss` | The specific symbol you sent in `purchase()`, if any. The return's `SS` must be exactly this value (empty when `ss` is not set). |
| `sharedSecret` | Usually inherited from the gateway. |

The return's `VS`, `SS`, `RES` and `SIGN` are read from the HTTP request that Omnipay gives the gateway: `Request::createFromGlobals()` by default. In a framework, pass the current Symfony request to the gateway:

```php
$gateway = new \Omnipay\Eplatby\Gateway(null, $symfonyRequest);
```
