# Začíname

omnipay-vub je brána pre [Omnipay](https://omnipay.thephpleague.com/), PHP knižnicu na spracovanie platieb. Platí cez [VÚB e-platby](https://www.vub.sk/firmy-a-podnikatelia/platby/eplatby.html), platobné tlačidlo VÚB banky: zákazník prejde do internet bankingu VÚB, tam platbu potvrdí a vráti sa na vašu návratovú URL s podpísaným výsledkom.

## Požiadavky {#requirements}

- PHP 8.4 alebo novšie (testované na 8.4, 8.5 a 8.6) s rozšírením `ext-hash`
- [`omnipay/common`](https://packagist.org/packages/omnipay/common) ^3.2 (nainštaluje sa s balíkom)
- HTTP klient pre Omnipay: ľubovoľný PSR-18 klient, ktorého nájde `php-http/discovery`, napríklad `php-http/guzzle7-adapter`. Samotná brána žiadne HTTP volania nerobí, Omnipay však klienta vytvára už pri vytvorení brány.
- zmluva s VÚB na e-platby, z ktorej dostanete svoj **MID** (identifikátor obchodníka) a **zdieľaný kľúč** (`sharedSecret`, kľúč pre HMAC). Kým ich nemáte, použite [testovací režim](./test-mode).

## Inštalácia {#installation}

```bash
composer require tomaj/omnipay-vub php-http/guzzle7-adapter
```

## Vytvorenie brány {#create-the-gateway}

Vytvorte ju cez továreň Omnipay (`Eplatby` je krátky názov brány) alebo cez `new`:

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

Zdieľaný kľúč držte v premenných prostredia alebo v úložisku tajomstiev, nikdy nie v kóde. Všetky parametre popisuje stránka [Konfigurácia](./configuration).

## 1. Pošlite zákazníka do banky {#_1-send-the-customer-to-the-bank}

Pokladňa vytvorí požiadavku `purchase()` so sumou, variabilným symbolom objednávky a vašou návratovou URL. `redirect()` vypíše HTML stránku, ktorej formulár sa sám odošle do VÚB:

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

Pred presmerovaním si objednávku uložte aj so sumou a variabilným symbolom: návratová URL ich bude potrebovať. Pozrite [Začatie platby](./purchase).

## 2. Overte návrat {#_2-verify-the-return}

VÚB pošle zákazníka na `rurl` s parametrami `VS`, `SS`, `RES` a `SIGN` v query stringu. Zavolajte `completePurchase()` s variabilným symbolom objednávky, ktorú očakávate:

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

::: warning Suma nie je podpísaná
VÚB podpisuje v návrate len variabilný symbol, špecifický symbol a výsledok. Sumu berte z uloženej objednávky, nikdy nie z požiadavky. Pozrite [Bezpečnosť](./security).
:::

## Ďalšie kroky {#next-steps}

- [Priebeh platby](./payment-flow): čo sa deje medzi e-shopom, prehliadačom a bankou
- [Konfigurácia](./configuration): všetky parametre
- [Príklady](../examples/): spustiteľné recepty vrátane e-shopu a falošnej banky na localhoste
- [Testovací režim](./test-mode): simulátor platieb
