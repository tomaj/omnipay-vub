# Upgrading from 3.x to 4.0

4.0 requires PHP 8.4, types the public API, makes the classes `final` and checks that the bank's return belongs to the order you are completing. Each change below shows the code before and after.

## PHP 8.4 and omnipay/common 3.2

`composer.json` requires `"php": "^8.4"` and `"omnipay/common": "^3.2"`. Upgrade PHP first, then:

```bash
composer require tomaj/omnipay-vub:^4.0
```

## Typed accessors

Getters return `?string`, setters take `?string` and return `static`. Calling a setter with an `int` from a file with `declare(strict_types=1)` throws a `TypeError`; values passed through `purchase([...])` or `initialize()` are still converted.

```php
// 3.x
$request->setVs(2024000123);

// 4.0
$request->setVs('2024000123');
// or
$gateway->purchase(['vs' => 2024000123]);
```

## Final classes

`Gateway`, `PurchaseRequest`, `PurchaseResponse`, `CompletePurchaseRequest`, `CompletePurchaseResponse` and `HmacSign` are `final` (`HmacSign` is also `readonly`). Code that extended them must wrap them instead, or use Omnipay's interfaces.

```php
// 3.x
class MyGateway extends \Omnipay\Eplatby\Gateway
{
    public function getName(): string
    {
        return 'My VÚB';
    }
}

// 4.0: compose instead of extending
final class MyPayments
{
    public function __construct(private \Omnipay\Eplatby\Gateway $gateway) {}

    public function pay(string $vs, string $amount): \Omnipay\Eplatby\Message\PurchaseResponse
    {
        return $this->gateway->purchase(['vs' => $vs, 'amount' => $amount, 'rurl' => '...'])->send();
    }
}
```

`Gateway::purchase()` and `Gateway::completePurchase()` now declare their concrete return types (`PurchaseRequest`, `CompletePurchaseRequest`), and `send()` returns `PurchaseResponse` / `CompletePurchaseResponse`, so `instanceof` checks are no longer needed.

## Exceptions

Every exception the package throws itself implements `Omnipay\Eplatby\Exception\EplatbyException` (which extends Omnipay's `OmnipayException`). Invalid requests and bank returns still throw Omnipay's `InvalidRequestException`.

```php
// 3.x
try {
    $response->getRedirectUrl();
} catch (\LogicException $e) {
}

// 4.0: Omnipay\Eplatby\Exception\LogicException still extends \LogicException
try {
    $response->getRedirectUrl();
} catch (\Omnipay\Common\Exception\OmnipayException $e) {
}
```

## The return request is read from the HTTP request, not `$_GET`

`CompletePurchaseRequest` reads `VS`, `SS`, `RES` and `SIGN` from the Symfony request Omnipay passes to the gateway (`Request::createFromGlobals()` by default), not from `$_GET`. Plain PHP keeps working; in frameworks and long-running servers, pass the current request:

```php
// 3.x: worked only when $_GET was populated
$gateway = Omnipay::create('Eplatby');

// 4.0
$gateway = Omnipay::create('Eplatby', null, $symfonyRequest);
// or
$gateway = new \Omnipay\Eplatby\Gateway($httpClient, $symfonyRequest);
```
