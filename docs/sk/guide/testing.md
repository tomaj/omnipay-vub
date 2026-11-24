# Testovanie integrácie

E-platby nemajú žiadne volania zo servera na server, takže všetko, čo robí banka, sa dá v teste zopakovať: platobná požiadavka je podpísaný formulár, návrat je podpísaný query string. Potrebujete zdieľaný kľúč z testovacej konfigurácie a `hash_hmac()`.

## Unit test návratovej URL {#unit-test-the-return-url}

Vytvorte návrat z banky ako Symfony požiadavku a odovzdajte ho bráne. V teste ho podpíšte priamo cez `hash_hmac()`, nie knižnicou, aby test overoval knižnicu, a nie ju len opakoval:

```php
use Omnipay\Eplatby\Gateway;
use Symfony\Component\HttpFoundation\Request;

$secret = str_repeat('1', 64);
$bankReturn = static function (string $vs, string $res, string $ss = '') use ($secret): Request {
    $query = ['VS' => $vs, 'RES' => $res, 'SIGN' => strtoupper(hash_hmac('sha256', $vs . $ss . $res, $secret))];
    if ($ss !== '') {
        $query['SS'] = $ss;
    }

    return Request::create('https://shop.example/return', 'GET', $query);
};

$gateway = new Gateway(null, $bankReturn('2024000123', 'OK'));
$gateway->setSharedSecret($secret);
$response = $gateway->completePurchase(['vs' => '2024000123'])->send();

echo var_export($response->isSuccessful(), true) . ' ' . $response->getRes() . "\n";
```

<!-- snippet: output -->
```text
true OK
```

Pokryte aspoň: `OK`, `FAIL`, `TOUT`, upravený `RES`, návrat inej objednávky, návratovú URL bez parametrov a ten istý návrat `OK` dvakrát. [Príklad Návratová URL](../examples/return-url) prežene presne tieto prípady jedným handlerom.

## Unit test pokladne {#unit-test-the-checkout}

Overujte `getRedirectUrl()` a `getRedirectData()` a očakávaný `SIGN` vypočítajte nezávisle, raz, cez príkazový riadok `openssl` ([Podpisy](./signatures)). Testovací vektor, ktorý si vypočítala sama knižnica, by nezachytil zlé poradie polí.

```php
$data = $gateway->purchase(['amount' => '10.00', 'vs' => '2024000123', 'cs' => '0308', 'rurl' => 'https://shop.example/return'])->send()->getRedirectData();

// openssl dgst -sha256 -hmac 1111...1 of '1234567810.0020240001230308https://shop.example/return'
echo $data['SIGN'] === '192E9B95242707C12828B2ABC0B483E8EB390A28A322893EC9CD1CC107E1CC90' ? "signature ok\n" : "signature changed\n";
```

<!-- snippet: output -->
```text
signature ok
```

## End-to-end bez banky {#end-to-end-without-a-bank}

[Príklad celého priebehu s presmerovaním](../examples/full-flow) spustí váš e-shop a 40-riadkovú falošnú banku cez `php -S` a skript hrá úlohu prehliadača: otvorí pokladňu, odošle formulár a nasleduje presmerovanie späť. V CI to bez siete trvá pár sekúnd. Skopírujte si `bank/index.php` do vlastných end-to-end testov a nasmerujte naň `testHost`.

## Ručný test so simulátorom {#manual-test-with-the-simulator}

Zapnite [testovací režim](./test-mode) s kľúčom simulátora a zaplaťte v prehliadači: `https://platby.tomaj.sk` (alebo vaša vlastná inštancia) ukáže prijatú požiadavku, či sedí podpis, a ponúkne odkazy späť na vašu návratovú URL pre každý výsledok.

## Pred spustením do produkcie {#before-going-live}

- [ ] produkčný MID a zdieľaný kľúč sa načítavajú z premenných prostredia a testovací režim je vypnutý;
- [ ] návratová URL je z prehliadača zákazníka dostupná cez HTTPS (banka na ňu len presmeruje, sama ju nevolá);
- [ ] pri skutočnej platbe malej sumy vráti `isSuccessful()` hodnotu `true` a objednávka sa označí ako zaplatená práve raz;
- [ ] zrušená platba (`FAIL`) nechá objednávku nezaplatenú a zákazník môže zaplatiť znova;
- [ ] objednávky, ktorých zákazník sa už nevrátil, sa párujú s bankovým výpisom.
