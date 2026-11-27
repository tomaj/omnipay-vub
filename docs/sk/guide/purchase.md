# Začatie platby

`purchase()` zostaví podpísanú platobnú požiadavku. Z vášho servera sa nič neposiela: prehliadaču zákazníka odovzdáte formulár, ktorý sa sám odošle do banky.

<<< @/../examples/purchase-form/run.php{php}

Výstup ([príklad Platobný formulár](../examples/purchase-form)):

<<< @/../examples/purchase-form/expected-output.txt{text}

## Požiadavka {#the-request}

`$gateway->purchase(array $parameters)` vráti `PurchaseRequest` s parametrami brány doplnenými o vaše ([všetky parametre](./configuration#purchase-parameters)). `send()` overí parametre, pridá podpis a vráti `PurchaseResponse`. Vyhodí:

| Výnimka | Kedy |
|---|---|
| `InvalidRequestException('The sharedSecret parameter is required')`, `... mid ...`, `... vs ...`, `... rurl ...`, `... amount ...` | parameter je `null` (nie je nastavený a brána nemá predvolenú hodnotu); pri `sharedSecret` aj prázdna hodnota alebo hodnota len z bielych znakov, vrátane predvoleného `''` brány |
| `InvalidRequestException('The vs parameter must be up to 10 digits')` | `vs` nemá 1 až 10 číslic (len `0` – `9`, bez medzier a znamienok) |
| `InvalidRequestException('The cs parameter must be up to 4 digits')` | `cs` je nastavený (nie `null` ani `''`), ale nemá 1 až 4 číslice |
| `InvalidRequestException('The vs parameter must be exactly 10 digits when cs is set')` | `cs` je nastavený a `vs` nemá presne 10 číslic ([Výber variabilného symbolu](#choosing-the-variable-symbol)) |
| `InvalidRequestException('The ss parameter must be up to 10 digits')` | `ss` je nastavený, ale nemá 1 až 10 číslic |
| `InvalidRequestException('The vs parameter must be exactly 10 digits when ss is set')` | `ss` je nastavený a `vs` nemá presne 10 číslic ([Výber variabilného symbolu](#choosing-the-variable-symbol)) |
| `InvalidRequestException('The rurl parameter must be an absolute https:// URL')` | `rurl` nie je absolútna `https://` URL s hostiteľom (je relatívna, má inú schému alebo obsahuje medzery či riadiace znaky); v testovacom režime sa prijme aj `http://` a správa znie `... must be an absolute https:// or http:// URL` |
| `InvalidRequestException('Amount precision is too high for currency.')` | `amount` má viac ako dve desatinné miesta |
| `InvalidRequestException('A negative amount is not allowed.')` | `amount` je záporná |

Všetky vyhodí už `getData()`, skôr než sa čokoľvek podpíše, takže chybne zadaná platba sa nikdy nedostane na chybovú stránku banky. Prijímané formáty:

| Parameter | Formát |
|---|---|
| `vs` | 1 až 10 číslic; presne 10, ak je nastavený `ss` alebo `cs` |
| `ss` | nepovinný; 1 až 10 číslic |
| `cs` | nepovinný; 1 až 4 číslice |
| `rurl` | absolútna `https://` URL s hostiteľom, bez medzier a riadiacich znakov; `http://` len v testovacom režime |

`getData()` vráti polia bez podpisu v tomto poradí: `MID`, `AMT`, `VS`, `CS`, `RURL`, `SS`, `REM`, `RSMS` (`null` pre prázdne nepovinné pole). `sendData($data)` pridá `SIGN`.

## Odpoveď {#the-response}

| Metóda | Vráti |
|---|---|
| `isSuccessful()` | `false`: platba ešte neprebehla |
| `isRedirect()` | `true` |
| `getRedirectMethod()` | `'POST'` |
| `getRedirectUrl()` | `https://ib.vub.sk/e-platbyeuro.aspx`, alebo simulátor v [testovacom režime](./test-mode) |
| `getRedirectData()` | polia z `getData()` plus `SIGN` |
| `redirect()` | pošle prehliadaču automaticky odosielanú HTML stránku z Omnipay (status 200) |
| `getRedirectResponse()` | tú istú stránku ako Symfony `Response`, pre frameworky, ktoré vracajú objekt odpovede |

`redirect()` vypíše stránku cez `echo`: zavolajte ho v controlleri namiesto vykreslenia šablóny a nič iné už nevypisujte. Vo frameworkoch vráťte `getRedirectResponse()`:

```php
$response = $gateway->purchase(['amount' => '10.00', 'vs' => '2024000123', 'rurl' => 'https://shop.example/return'])->send();

$httpResponse = $response->getRedirectResponse();   // Symfony\Component\HttpFoundation\Response
echo $httpResponse->getStatusCode() . ', ' . substr_count((string) $httpResponse->getContent(), 'type="hidden"') . " hidden fields\n";
```

<!-- snippet: output -->
```text
200, 9 hidden fields
```

Stránka z Omnipay odošle všetky polia, aj prázdne nepovinné (`SS`, `RSMS`, ... ako prázdne reťazce). Pre prehliadače bez JavaScriptu má tlačidlo „Continue“.

## Vlastný formulár {#your-own-form}

Ak chcete zákazníkovi namiesto automaticky odosielanej stránky ukázať rekapituláciu a tlačidlo „Zaplatiť cez VÚB“, vykreslite formulár z `getRedirectUrl()` a `getRedirectData()`. Každú hodnotu escapujte; prázdne polia môžete vynechať: prázdne polia podpis nemenia.

<<< @/../examples/custom-form/run.php{php}

Vykreslený formulár nájdete na stránke príkladu [Vlastné platobné tlačidlo](../examples/custom-form).

## Výber variabilného symbolu {#choosing-the-variable-symbol}

Podľa variabilného symbolu návrat nájde svoju objednávku a podpis banky k nemu viaže výsledok. Variabilný symbol by mal byť:

- **jedinečný pre každú objednávku** (a pre každý pokus o platbu, ak to vyžaduje vaše účtovníctvo), nikdy sa neopakuje;
- **len z číslic, najviac 10**, ako vyžaduje banka;
- **presne 10-miestny**, ak používate aj špecifické alebo konštantné symboly, napríklad ID objednávky doplnené nulami: `str_pad((string) $id, 10, '0', STR_PAD_LEFT)`. Banka podpisuje `VS + SS` bez oddeľovača; pri variabilnom symbole plnej dĺžky sa dá podpísaný `VS + SS` rozdeliť len jedným spôsobom. Inak `purchase()` aj `completePurchase()` vyhodia `InvalidRequestException('The vs parameter must be exactly 10 digits when ss is set')` ([Bezpečnosť](./security#variable-and-specific-symbol)). Platobná požiadavka podpisuje bez oddeľovača aj `VS + SS + CS`, takže s konštantným symbolom `purchase()` vyhodí `InvalidRequestException('The vs parameter must be exactly 10 digits when cs is set')`. Bez špecifického a konštantného symbolu funguje ľubovoľná dĺžka do 10 číslic, pevná dĺžka je však aj tak bezpečnejšia voľba.

## Vlastné podpísanie reťazca {#signing-a-string-yourself}

`PurchaseRequest::generateSignature(string $data)` vráti HMAC-SHA256 ľubovoľného reťazca veľkými písmenami, podpísaný zdieľaným kľúčom (`sharedSecret`) požiadavky, rovnako ako `new HmacSign()->sign($data, $secret)` ([Podpisy](./signatures)).
