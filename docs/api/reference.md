# API reference

Every public class and method of the package. All classes are `final`; namespace `Omnipay\Eplatby`. Methods inherited from Omnipay are listed where they matter for this gateway; the rest behave as in [omnipay/common](https://github.com/thephpleague/omnipay-common).

## `Gateway`

`final class Gateway extends Omnipay\Common\AbstractGateway`

| Method | Returns | Description |
|---|---|---|
| `__construct(?ClientInterface $httpClient = null, ?Request $httpRequest = null)` | | Omnipay's constructor. `$httpRequest` (Symfony) is where `completePurchase()` reads the return; `null` means `Request::createFromGlobals()`. `$httpClient` `null` discovers a PSR-18 client. |
| `getName()` | `string` | `'VUB ePlatby Gateway'` |
| `getShortName()` | `string` | `'Eplatby'`, the name for `Omnipay::create('Eplatby')` |
| `getDefaultParameters()` | `array<string, string>` | `['mid' => '', 'sharedSecret' => '', 'testHost' => '']` |
| `initialize(array $parameters = [])` | `$this` | Resets the parameters to the defaults, then sets the given ones (Omnipay) |
| `getParameters()` | `array` | All parameters (Omnipay) |
| `getMid()` / `setMid(?string $value)` | `?string` / `static` | Merchant ID |
| `getSharedSecret()` / `setSharedSecret(?string $value)` | `?string` / `static` | HMAC-SHA256 key |
| `getTestMode()` / `setTestMode($value)` | `?bool` / `static` | Post to the simulator (Omnipay) |
| `getTestHost()` / `setTestHost(?string $value)` | `?string` / `static` | Simulator host for test mode |
| `purchase(array $parameters = [])` | `Message\PurchaseRequest` | A new payment, with the gateway's parameters overridden by `$parameters` |
| `completePurchase(array $parameters = [])` | `Message\CompletePurchaseRequest` | Verifies the bank's return, with the gateway's parameters overridden by `$parameters` |
| `supportsPurchase()`, `supportsCompletePurchase()` | `bool` | `true` (Omnipay); the other `supports*()` return `false` |

Getters return `null` for a parameter that is not a string or an integer (an integer is returned as a string).

## `Message\PurchaseRequest`

`final class PurchaseRequest extends Omnipay\Common\Message\AbstractRequest`

| Member | Returns | Description |
|---|---|---|
| `getData()` | `array<string, ?string>` | `MID`, `AMT`, `VS`, `CS`, `RURL`, `SS`, `REM`, `RSMS`. Throws `InvalidRequestException` when `sharedSecret`, `mid`, `vs`, `rurl` or `amount` is `null`, `sharedSecret` is empty or whitespace, `ss` is set and is not 1-10 digits or `vs` is not exactly 10 digits, or the amount is invalid. |
| `send()` | `PurchaseResponse` | `sendData(getData())` |
| `sendData(mixed $data)` | `PurchaseResponse` | Adds `SIGN` = HMAC-SHA256 of `MID + AMT + VS + SS + CS + RURL`. Throws `InvalidRequestException('Purchase data must be an array')`. |
| `generateSignature(string $data)` | `string` | Uppercase hex HMAC-SHA256 of `$data` with `sharedSecret`; throws `InvalidRequestException('The sharedSecret parameter is required')` when it is `null`, empty or whitespace |
| `getEndpoint()` | `string` | `https://ib.vub.sk/e-platbyeuro.aspx`; in test mode `<testHost or https://platby.tomaj.sk>/payment/eplatby-hmac` |
| `getMid()` / `setMid(?string)` | `?string` / `static` | `MID` |
| `getSharedSecret()` / `setSharedSecret(?string)` | `?string` / `static` | HMAC key |
| `getTestHost()` / `setTestHost(?string)` | `?string` / `static` | Simulator host |
| `getVs()` / `setVs(?string)` | `?string` / `static` | `VS`, variable symbol |
| `getSs()` / `setSs(?string)` | `?string` / `static` | `SS`, specific symbol |
| `getCs()` / `setCs(?string)` | `?string` / `static` | `CS`, constant symbol |
| `getRurl()` / `setRurl(?string)` | `?string` / `static` | `RURL`, return URL |
| `getRem()` / `setRem(?string)` | `?string` / `static` | `REM`, notification e-mail |
| `getRsms()` / `setRsms(?string)` | `?string` / `static` | `RSMS`, notification phone number |
| `getAmount()` / `setAmount($value)` | `?string` / `static` | `AMT`, formatted with two decimals (Omnipay) |
| `getTestMode()` / `setTestMode($value)` | `?bool` / `static` | (Omnipay) |

## `Message\PurchaseResponse`

`final class PurchaseResponse extends Omnipay\Common\Message\AbstractResponse implements RedirectResponseInterface`

| Method | Returns | Description |
|---|---|---|
| `isSuccessful()` | `bool` | Always `false` |
| `isRedirect()` | `bool` | Always `true` |
| `getRedirectUrl()` | `string` | The request's `getEndpoint()`. Throws `Exception\LogicException('PurchaseResponse requires a PurchaseRequest')` when created for another request. |
| `getRedirectMethod()` | `string` | `'POST'` |
| `getRedirectData()` | `array` | The signed form fields |
| `getRedirectResponse()` | `Symfony\Component\HttpFoundation\Response` | Omnipay's auto-submitting HTML page |
| `redirect()` | `void` | Sends `getRedirectResponse()` (Omnipay) |
| `getData()` | `mixed` | The same fields as `getRedirectData()` |
| `getRequest()` | `RequestInterface` | The `PurchaseRequest` |

## `Message\CompletePurchaseRequest`

`final class CompletePurchaseRequest extends Omnipay\Common\Message\AbstractRequest`

| Member | Returns | Description |
|---|---|---|
| `getData()` | `array{RES: string, VS: string, SS: string}` | Reads `VS`, `SS`, `RES`, `SIGN` from the query string and runs [the checks](../guide/complete-purchase#the-checks). Throws `InvalidRequestException`. Missing or non-string query parameters are read as `''`. |
| `send()` | `CompletePurchaseResponse` | `sendData(getData())` |
| `sendData(mixed $data)` | `CompletePurchaseResponse` | Wraps `$data` |
| `getSharedSecret()` / `setSharedSecret(?string)` | `?string` / `static` | HMAC key |
| `getVs()` / `setVs(?string)` | `?string` / `static` | Expected variable symbol (required) |
| `getSs()` / `setSs(?string)` | `?string` / `static` | Expected specific symbol; `null` expects none |

## `Message\CompletePurchaseResponse`

`final class CompletePurchaseResponse extends Omnipay\Common\Message\AbstractResponse`

| Member | Returns | Description |
|---|---|---|
| `const string SUCCESS = 'OK'` | | The `RES` of a paid return |
| `isSuccessful()` | `bool` | `getRes() === 'OK'` |
| `getRes()` | `?string` | `'OK'`, `'FAIL'` or `'TOUT'` |
| `getVs()` | `?string` | Returned (verified) variable symbol |
| `getSs()` | `?string` | Returned (verified) specific symbol, `''` when none |
| `getData()` | `mixed` | `['RES' => ..., 'VS' => ..., 'SS' => ...]` |
| `isRedirect()`, `isCancelled()` | `bool` | `false` (Omnipay) |
| `getMessage()`, `getCode()`, `getTransactionReference()` | `null` | (Omnipay) |

The getters return `null` when the data has no string under that key (only possible when you build the response yourself).

## `Sign\HmacSign`

`final readonly class HmacSign`

| Method | Returns | Description |
|---|---|---|
| `sign(string $input, string $secret)` | `string` | `strtoupper(hash_hmac('sha256', $input, $secret))` |

## Exceptions

| Class | Extends / implements | Thrown when |
|---|---|---|
| `Exception\EplatbyException` | interface, extends `Omnipay\Common\Exception\OmnipayException`, `\Throwable` | implemented by every exception the package defines |
| `Exception\LogicException` | `\LogicException`, implements `EplatbyException` | `PurchaseResponse::getRedirectUrl()` without a `PurchaseRequest` |
| `Omnipay\Common\Exception\InvalidRequestException` | Omnipay, implements `OmnipayException` | missing parameters, invalid amount, invalid signature, symbol mismatch |

See [Error handling](../guide/errors) for every message.
