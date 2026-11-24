# Testovací režim

So zapnutým `testMode` odošle `purchase()` formulár na simulátor platieb namiesto do VÚB. Nič iné sa nemení: podpisy, návrat aj `completePurchase()` fungujú rovnako.

| Režim | `getRedirectUrl()` |
|---|---|
| produkcia (`testMode` nenastavený alebo `false`) | `https://ib.vub.sk/e-platbyeuro.aspx` |
| testovací režim | `https://platby.tomaj.sk/payment/eplatby-hmac` |
| testovací režim s `testHost` | `<testHost>/payment/eplatby-hmac` |

```php
$gateway->setTestMode(true);
echo $gateway->purchase(['vs' => '1', 'rurl' => 'https://shop.example/return'])->getEndpoint() . "\n";

$gateway->setTestHost('http://127.0.0.1:8080');   // your own simulator
echo $gateway->purchase(['vs' => '1', 'rurl' => 'https://shop.example/return'])->getEndpoint() . "\n";

$gateway->setTestMode(false);
echo $gateway->purchase(['vs' => '1', 'rurl' => 'https://shop.example/return'])->getEndpoint() . "\n";
```

<!-- snippet: output -->
```text
https://platby.tomaj.sk/payment/eplatby-hmac
http://127.0.0.1:8080/payment/eplatby-hmac
https://ib.vub.sk/e-platbyeuro.aspx
```

`testHost` sa používa len v testovacom režime; prázdny `testHost` znamená `https://platby.tomaj.sk`.

## Simulátor platieb {#payment-simulator}

[tomaj/payment-simulator](https://github.com/tomaj/payment-simulator) implementuje HMAC endpoint e-platieb (aj ďalšie platobné tlačidlá slovenských bánk). Podpisuje zdieľaným kľúčom `1111111111111111111111111111111111111111111111111111111111111111` (64 × `1`) a prijme ľubovoľný MID, ukáže, či podpis vašej požiadavky sedí, a ponúkne odkazy späť na vašu `RURL` s podpísaným výsledkom `OK`, `FAIL` alebo `TOUT`. `https://platby.tomaj.sk` je verejná inštancia; pre CI alebo súkromné dáta si spustite vlastnú v Dockeri a nasmerujte na ňu `testHost`.

::: warning Špecifický symbol v simulátore
Simulátor zatiaľ podpisuje špecifický symbol na inej pozícii ([payment-simulator#26](https://github.com/tomaj/payment-simulator/issues/26)), takže pri platbách so `ss` hlási zlý podpis. Platby bez špecifického symbolu fungujú.
:::

## Falošná banka na localhoste {#a-fake-bank-on-localhost}

Na automatické testy simulátor nepotrebujete: e-platby sú dve podpísané presmerovania, ktoré zvládne zahrať pár riadkov PHP. [Príklad celého priebehu s presmerovaním](../examples/full-flow) spustí e-shop a falošnú banku cez `php -S` a ovláda ich ako prehliadač; [Testovanie integrácie](./testing) ukazuje unit testy úplne bez servera.

## Testovacie prostredie VÚB {#vub-test-environment}

VÚB prevádzkuje pre obchodníkov aj vlastné testovacie prostredie (`https://nib.vub.sk/nepay/merchant`). Vyžaduje testovacie prístupové údaje od VÚB a má inú cestu ako simulátor, takže naň `testHost` nasmerovať nemôžete; o prístup a aktuálnu URL požiadajte VÚB (e_platby@vub.sk).

## Nikdy v produkcii {#never-in-production}

Kľúč simulátora je verejný: e-shop, ktorý ním overuje návraty, prijme návrat `OK`, ktorý si môže podpísať ktokoľvek. Kľúč simulátora používajte len spolu s testovacím režimom a kľúč aj režim načítajte z premenných prostredia, aby ich produkčné nasadenie nemohlo omylom prevziať.
