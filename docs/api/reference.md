# API reference

All classes are `final`. Namespace `Omnipay\Eplatby`.

## `Gateway`

`extends Omnipay\Common\AbstractGateway`

| Method | Description |
|---|---|
| `__construct(?ClientInterface $httpClient = null, ?Request $httpRequest = null)` | Omnipay's constructor. `$httpRequest` (Symfony) is where `completePurchase()` reads the return; defaults to `Request::createFromGlobals()`. |
| `getName(): string` | `'VUB ePlatby Gateway'` |
| `getShortName(): string` | `'Eplatby'` (for `Omnipay::create('Eplatby')`) |
| `getDefaultParameters(): array` | `['mid' => '', 'sharedSecret' => '', 'testHost' => '']` (+ Omnipay's `testMode`) |
| `getMid(): ?string` / `setMid(?string): static` | Merchant ID |
| `getSharedSecret(): ?string` / `setSharedSecret(?string): static` | HMAC key |
| `getTestHost(): ?string` / `setTestHost(?string): static` | Simulator host for test mode |
| `getTestMode(): bool` / `setTestMode(bool): static` | Omnipay |
| `purchase(array $parameters = []): Message\PurchaseRequest` | New payment |
| `completePurchase(array $parameters = []): Message\CompletePurchaseRequest` | Verify the return |

## `Message\PurchaseRequest`

`extends Omnipay\Common\Message\AbstractRequest`

| Method | Description |
|---|---|
| `getData(): array<string, ?string>` | `MID`, `AMT`, `VS`, `CS`, `RURL`, `SS`, `REM`, `RSMS`. Throws `InvalidRequestException` without `sharedSecret`, `mid`, `vs` or `rurl`. |
| `send(): PurchaseResponse` | `sendData(getData())` |
| `sendData(mixed $data): PurchaseResponse` | Adds `SIGN`. Throws `InvalidRequestException` when `$data` is not an array. |
| `generateSignature(string $data): string` | Uppercase HMAC-SHA256 of `$data` with `sharedSecret` |
| `getEndpoint(): string` | Production or test-mode URL, see [Test mode](../guide/test-mode) |
| `get/setMid`, `get/setSharedSecret`, `get/setTestHost`, `get/setVs`, `get/setCs`, `get/setSs`, `get/setRurl`, `get/setRem`, `get/setRsms` | `?string` getters, setters take `?string` and return `static` |
| `getAmount(): ?string` / `setAmount()` | Omnipay |

## `Message\PurchaseResponse`

`extends Omnipay\Common\Message\AbstractResponse implements RedirectResponseInterface`

| Method | Description |
|---|---|
| `isSuccessful(): bool` | Always `false` |
| `isRedirect(): bool` | Always `true` |
| `getRedirectUrl(): string` | The request's endpoint. Throws `Exception\LogicException` when the response was created for another request type. |
| `getRedirectMethod(): string` | `'POST'` |
| `getRedirectData(): array` | The signed form fields |
| `redirect(): void` | Omnipay: prints the auto-submitting form |

## `Message\CompletePurchaseRequest`

`extends Omnipay\Common\Message\AbstractRequest`

| Method | Description |
|---|---|
| `getData(): array{RES: string, VS: string, SS: string}` | Verifies the return, see [Operations](../guide/operations#completepurchase). Throws `InvalidRequestException`. |
| `send(): CompletePurchaseResponse` | `sendData(getData())` |
| `sendData(mixed $data): CompletePurchaseResponse` | Wraps the data |
| `get/setSharedSecret` | HMAC key |
| `get/setVs` | Expected variable symbol (required) |
| `get/setSs` | Expected specific symbol (optional) |

Missing or non-string query parameters are read as `''`.

## `Message\CompletePurchaseResponse`

`extends Omnipay\Common\Message\AbstractResponse`

| Member | Description |
|---|---|
| `const string SUCCESS = 'OK'` | |
| `isSuccessful(): bool` | `RES === 'OK'` |
| `getRes(): ?string` | `OK`, `FAIL` or `TOUT` |
| `getVs(): ?string` | Returned variable symbol |
| `getSs(): ?string` | Returned specific symbol (`''` when none) |

## `Sign\HmacSign`

`final readonly class`

| Method | Description |
|---|---|
| `sign(string $input, string $secret): string` | Uppercase hex HMAC-SHA256 |

## Exceptions

| Class | When |
|---|---|
| `Omnipay\Common\Exception\InvalidRequestException` | Missing parameters, invalid signature, symbol mismatch |
| `Exception\LogicException` (`extends \LogicException`) | Programming errors |
| `Exception\EplatbyException` (interface, `extends OmnipayException`) | Implemented by every exception the package defines |

Catch `Omnipay\Common\Exception\OmnipayException` to handle every exception of the gateway.
