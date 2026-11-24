# Návratová URL

Po platbe VÚB presmeruje zákazníka na vašu `rurl` s výsledkom v query stringu:

```
https://shop.example/return?VS=2024000123&RES=OK&SIGN=6ADD04387C7A8787B51C1A5E48C546F66335BCF3DF18E93735A618470609C87F
```

| Pole | Význam |
|---|---|
| `VS` | variabilný symbol, ktorý ste poslali |
| `SS` | špecifický symbol, ktorý ste poslali; chýba, ak ho platba nemala |
| `RES` | `OK` zaplatené, `FAIL` zamietnuté alebo zrušené, `TOUT` vypršal čas |
| `SIGN` | HMAC-SHA256 z `VS + SS + RES` so zdieľaným kľúčom, hexadecimálne veľkými písmenami |

`completePurchase()` z toho urobí overenú odpoveď pre jednu objednávku.

<<< @/../examples/return-url/run.php{php}

<<< @/../examples/return-url/expected-output.txt{text}

## Kontroly {#the-checks}

`$gateway->completePurchase(['vs' => ..., 'ss' => ...])->send()` spustí tieto kontroly v tomto poradí. Prvá, ktorá zlyhá, vyhodí `InvalidRequestException` z Omnipay so správou z tabuľky:

| # | Kontrola | Správa |
|---|---|---|
| 1 | `sharedSecret` je nastavený a nie je prázdny ani len z bielych znakov | `The sharedSecret parameter is required` |
| 2 | `vs` bol odovzdaný a nie je prázdny | `The vs parameter is required` |
| 3 | ak je odovzdaný `ss` (nie `null` ani `''`): `ss` má najviac 10 číslic a `vs` presne 10 číslic | `The ss parameter must be up to 10 digits`, `The vs parameter must be exactly 10 digits when ss is set` |
| 4 | `SIGN` sa rovná HMAC-SHA256 z `VS + SS + RES` (porovnané cez `hash_equals()`) | `incorect signature` (sic) |
| 5 | vrátený `VS` je presne očakávaný `vs` | `Variable symbol mismatch` |
| 6 | vrátený `SS` je presne očakávaný `ss` (`''`, keď je `ss` `null`) | `Specific symbol mismatch` |

Kontroly 1 až 3 sa týkajú vašej konfigurácie a objednávky, nie návratu: zlyhajú pri každom návrate tejto objednávky. Chýbajúci parameter v query stringu (alebo parameter, ktorý nie je reťazec, napríklad `RES[]=OK`) sa číta ako `''`, takže požiadavka bez `SIGN` zlyhá na kontrole 4. Kontrola podpisu beží pred kontrolami symbolov: návrat so zlým podpisom vždy hlási `incorect signature`.

## Odpoveď {#the-response}

Keď prejdú všetky kontroly, `send()` vráti `CompletePurchaseResponse`:

| Metóda | Vráti |
|---|---|
| `isSuccessful()` | `true`, keď je `RES` rovné `OK` (`CompletePurchaseResponse::SUCCESS`) |
| `getRes()` | `'OK'`, `'FAIL'` alebo `'TOUT'` (to, čo banka podpísala) |
| `getVs()` | vrátený, overený variabilný symbol |
| `getSs()` | vrátený, overený špecifický symbol; `''`, keď žiadny nie je |
| `getData()` | `['RES' => ..., 'VS' => ..., 'SS' => ...]` |
| `isRedirect()`, `isCancelled()` | `false` (predvolené v Omnipay) |
| `getMessage()`, `getCode()`, `getTransactionReference()` | `null` (predvolené v Omnipay) |

## Spracovanie výsledku {#handling-the-result}

- **Výnimka**: požiadavku nevytvorila banka pre túto objednávku: je podvrhnutá, upravená alebo je to pravý návrat inej objednávky. Objednávku nemeňte. Zalogujte to a zákazníkovi ukážte stránku objednávky s jej skutočným stavom.
- **`isSuccessful()`**: označte objednávku ako zaplatenú, a to raz. Suma je tá, ktorá je uložená pri objednávke: banka sumu nepodpisuje.
- **`FAIL` alebo `TOUT`**: zákazník nezaplatil. Ponúknite mu zaplatiť znova. Či nový pokus ponechá variabilný symbol, závisí od vášho účtovníctva; brána prijme oboje.

## O ktorú objednávku ide? {#which-order-is-it}

Objednávku načítajte buď zo session zákazníka, alebo podľa vráteného `VS` (`$request->query->get('VS')`), a do `completePurchase()` potom odovzdajte **jej** variabilný symbol. Obe cesty sú bezpečné: kontrola `vs` zabráni tomu, aby pravý úspešný návrat jednej objednávky (napríklad lacnejšej) dokončil inú.

```php
$vs = $_GET['VS'] ?? '';
$order = $orders[$vs] ?? null;          // your lookup; unknown VS: show a 404 page
$response = $gateway->completePurchase(['vs' => $order['vs'], 'ss' => $order['ss']])->send();
echo $response->isSuccessful() ? "paid\n" : "not paid\n";
```

<!-- snippet: output -->
```text
paid
```

## Odovzdajte správnu HTTP požiadavku {#pass-the-right-http-request}

Query string sa číta zo Symfony požiadavky, s ktorou bola brána vytvorená, nie z `$_GET`. Čisté PHP funguje bez ďalšieho nastavovania (`Request::createFromGlobals()`); vo frameworkoch a na dlho bežiacich serveroch odovzdajte aktuálnu požiadavku: `new Gateway(null, $request)`, pozrite [Konfigurácia](./configuration#the-http-request-in-frameworks).

## Špecifický symbol {#specific-symbol}

Ak ste v `purchase()` poslali `ss`, odovzdajte sem ten istý `ss`. Ak ste žiadny neposlali, neodovzdávajte ani tu žiadny: návrat, ktorý `SS` obsahuje, sa potom odmietne. Pozrite [príklad Špecifický symbol](../examples/specific-symbol).
