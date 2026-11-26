---
title: Prechod z 3.x
---

# Prechod z 3.x na 4.0

Verzia 4.0 vyžaduje PHP 8.4, typuje verejné API, robí triedy `final` a kontroluje, že návrat z banky patrí k objednávke, ktorú dokončujete. Každá zmena nižšie ukazuje kód pred a po. Anglický originál je v [UPGRADE-4.0.md](https://github.com/tomaj/omnipay-vub/blob/master/UPGRADE-4.0.md).

## `completePurchase()` vyžaduje očakávaný variabilný symbol {#completepurchase-requires-the-expected-variable-symbol}

Návrat z banky je podpísaný len cez variabilný symbol a výsledok. Verzia 3.x prijala ľubovoľný správne podpísaný návrat, takže úspešná návratová URL jednej objednávky dokončila aj inú. Verzia 4.0 vyžaduje variabilný symbol objednávky, ktorú dokončujete, a porovná ho s podpísaným `VS`:

<!-- snippet: skip -->
```php
// 3.x
$response = $gateway->completePurchase()->send();
if ($response->isSuccessful()) {
    $order = $orders->findByVs($response->getVs());
    $order->markPaid();
}

// 4.0
$order = $orders->findByVs($request->getQuery('VS')); // or the order from your session
$response = $gateway->completePurchase(['vs' => $order->vs])->send();
// throws InvalidRequestException('Variable symbol mismatch') when the signed VS is another one
if ($response->isSuccessful()) {
    $order->markPaid(); // the amount is the one stored with the order: the bank does not sign it
}
```

Bez `vs` vyhodí `completePurchase()->send()` výnimku `InvalidRequestException('The vs parameter is required')`. Porovnanie je presné (`'0123'` nie je `'123'`), takže odovzdajte variabilný symbol presne tak, ako ste ho poslali v `purchase()`.

## Špecifický symbol je podpísaný {#the-specific-symbol-is-signed}

VÚB podpisuje špecifický symbol (`SS`): platobná požiadavka sa podpisuje cez `MID + AMT + VS + SS + CS + RURL` a návrat cez `VS + SS + RES`. Bez špecifického symbolu sa nič nemení. So špecifickým symbolom odovzdajte ten istý `ss` aj do `completePurchase()`:

<!-- snippet: skip -->
```php
// 3.x: SS was posted but not signed, so VÚB rejected the payment
$gateway->purchase(['vs' => '2024000123', 'ss' => '77', 'amount' => '10.00', 'rurl' => '...'])->send();
$gateway->completePurchase()->send();

// 4.0
$gateway->purchase(['vs' => '2024000123', 'ss' => '77', 'amount' => '10.00', 'rurl' => '...'])->send();
$gateway->completePurchase(['vs' => '2024000123', 'ss' => '77'])->send();
// throws InvalidRequestException('Specific symbol mismatch') when the returned SS is another one
```

So špecifickým symbolom musí mať variabilný symbol presne 10 číslic a `ss` 1 až 10 číslic. VÚB podpisuje `VS + SS` bez oddeľovača a jednoznačný podpisovaný reťazec zaručí len variabilný symbol plnej dĺžky. ID objednávky doplňte nulami, túto hodnotu uložte k objednávke a odovzdajte ju do oboch volaní:

<!-- snippet: skip -->
```php
// throws InvalidRequestException('The vs parameter must be exactly 10 digits when ss is set')
$gateway->purchase(['vs' => '123', 'ss' => '77', 'amount' => '10.00', 'rurl' => '...'])->send();

// 4.0
$vs = str_pad((string) $order->id, 10, '0', STR_PAD_LEFT); // '0000000123'
$gateway->purchase(['vs' => $vs, 'ss' => '77', 'amount' => '10.00', 'rurl' => '...'])->send();
$gateway->completePurchase(['vs' => $vs, 'ss' => '77'])->send();
```

## Prázdny zdieľaný kľúč sa odmietne {#an-empty-shared-secret-is-rejected}

Verzia 3.x podpisovala akýmkoľvek nastaveným `sharedSecret`, vrátane predvoleného `''` brány: nasadenie s chýbajúcou premennou prostredia podpisovalo platby a prijímalo návraty s prázdnym HMAC kľúčom, ktorý si vie vypočítať ktokoľvek. Verzia 4.0 vyhodí z `purchase()` aj `completePurchase()` výnimku `InvalidRequestException('The sharedSecret parameter is required')`, keď je kľúč `null`, prázdny alebo len z bielych znakov:

<!-- snippet: skip -->
```php
// 3.x: signed with an empty key when VUB_SHARED_SECRET was not set
$gateway->setSharedSecret((string) getenv('VUB_SHARED_SECRET'));

// 4.0: the gateway throws on the first payment; better fail when the application starts
$gateway->setSharedSecret(getenv('VUB_SHARED_SECRET') ?: throw new RuntimeException('VUB_SHARED_SECRET is not set'));
```

## Platobné symboly a návratová URL sa kontrolujú {#payment-symbols-and-the-return-url-are-validated}

Verzia 3.x podpísala a odoslala akýkoľvek `vs` a `cs` bez špecifického symbolu a akúkoľvek `rurl`; chybnú hodnotu VÚB odmietla až po presmerovaní zákazníka, na chybovej stránke banky. Verzia 4.0 ich skontroluje v `purchase()` (`getData()`, `send()`) skôr, než sa čokoľvek podpíše, a vyhodí `InvalidRequestException`:

| Parameter | Prijme sa | Správa |
|---|---|---|
| `vs` | 1 až 10 číslic | `The vs parameter must be up to 10 digits` |
| `cs` | nenastavený, `''` alebo 1 až 4 číslice | `The cs parameter must be up to 4 digits` |
| `rurl` | absolútna `https://` URL s hostiteľom, bez medzier a riadiacich znakov; v testovacom režime aj `http://` | `The rurl parameter must be an absolute https:// URL` (v testovacom režime `... https:// or http:// URL`) |

<!-- snippet: skip -->
```php
// 3.x: signed and posted; VÚB showed an error page after the redirect
$gateway->purchase(['vs' => 'ORD-123', 'cs' => '00308', 'amount' => '10.00', 'rurl' => '/payment/return'])->send();

// 4.0: throws InvalidRequestException('The vs parameter must be up to 10 digits') before signing
$gateway->purchase([
    'vs' => '2024000123',                         // 1 to 10 digits
    'cs' => '0308',                               // optional, 1 to 4 digits
    'amount' => '10.00',
    'rurl' => 'https://shop.example/payment/return', // absolute https:// (http:// only in test mode)
])->send();
```

Obyčajná `http://` návratová URL funguje len so zapnutým `testMode`, pre e-shop bežiaci lokálne. ID objednávok skontrolujte skôr, než ich použijete ako `vs`, a `rurl` skladajte z absolútnej HTTPS adresy vášho webu.

## PHP 8.4 a omnipay/common 3.2 {#php-8-4-and-omnipay-common-3-2}

`composer.json` vyžaduje `"php": "^8.4"` a `"omnipay/common": "^3.2"`. Najprv aktualizujte PHP, potom:

```bash
composer require tomaj/omnipay-vub:^4.0
```

## Typované prístupové metódy {#typed-accessors}

Gettery vracajú `?string`, settery berú `?string` a vracajú `static`. Volanie settera s `int` zo súboru s `declare(strict_types=1)` vyhodí `TypeError`; hodnoty odovzdané cez `purchase([...])` alebo `initialize()` sa stále konvertujú.

<!-- snippet: skip -->
```php
// 3.x
$request->setVs(2024000123);

// 4.0
$request->setVs('2024000123');
// or
$gateway->purchase(['vs' => 2024000123]);
```

## Triedy `final` {#final-classes}

`Gateway`, `PurchaseRequest`, `PurchaseResponse`, `CompletePurchaseRequest`, `CompletePurchaseResponse` a `HmacSign` sú `final` (`HmacSign` je aj `readonly`). Kód, ktorý od nich dedil, ich musí namiesto toho obaliť alebo použiť rozhrania Omnipay.

<!-- snippet: skip -->
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

`Gateway::purchase()` a `Gateway::completePurchase()` teraz deklarujú konkrétne návratové typy (`PurchaseRequest`, `CompletePurchaseRequest`) a `send()` vracia `PurchaseResponse` / `CompletePurchaseResponse`, takže kontroly cez `instanceof` už nie sú potrebné.

## Výnimky {#exceptions}

Každá výnimka, ktorú balík sám vyhadzuje, implementuje `Omnipay\Eplatby\Exception\EplatbyException` (rozširuje `OmnipayException` z Omnipay). Neplatné požiadavky a návraty z banky stále vyhadzujú `InvalidRequestException` z Omnipay.

<!-- snippet: skip -->
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

## Návrat sa číta z HTTP požiadavky, nie z `$_GET` {#the-return-request-is-read-from-the-http-request-not-get}

`CompletePurchaseRequest` číta `VS`, `SS`, `RES` a `SIGN` zo Symfony požiadavky, ktorú Omnipay odovzdá bráne (predvolene `Request::createFromGlobals()`), nie z `$_GET`. Čisté PHP funguje ďalej; vo frameworkoch a na dlho bežiacich serveroch odovzdajte aktuálnu požiadavku:

```php
use Omnipay\Omnipay;

// 3.x: worked only when $_GET was populated
$gateway = Omnipay::create('Eplatby');

// 4.0
$gateway = Omnipay::create('Eplatby', null, $symfonyRequest);
// or
$gateway = new \Omnipay\Eplatby\Gateway($httpClient, $symfonyRequest);
```
