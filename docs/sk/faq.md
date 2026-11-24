# Časté otázky

## Prečo `completePurchase()` vyhodí „The vs parameter is required“?

Od verzie 4.0 odovzdávate variabilný symbol objednávky, ktorú dokončujete: `completePurchase(['vs' => $order->vs])`. Zabráni to tomu, aby platný návrat jednej objednávky dokončil inú. Pozrite [Prechod z 3.x](./upgrade).

## Ako overím zaplatenú sumu?

Z návratu nijako: VÚB ju neposiela ani nepodpisuje. Použite sumu uloženú pri objednávke, ktorej variabilný symbol ste odovzdali do `completePurchase()`, a používajte variabilný symbol jedinečný pre každú objednávku. Pre účtovníctvo platby párujte s bankovým výpisom.

## „incorect signature“ pri každom návrate

- `sharedSecret` sa líši od kľúča, ktorým bol návrat podpísaný (testovací režim používa kľúč simulátora, produkcia kľúč od VÚB);
- váš router mení query string alebo ho dekóduje dvakrát;
- vo frameworku alebo na dlho bežiacom serveri číta brána inú požiadavku než aktuálnu: odovzdajte ju, `new Gateway(null, $request)`.

Správa je v kóde napísaná ako `incorect`; rozhodujte podľa triedy výnimky, nie podľa textu.

## „Specific symbol mismatch“

Poslali ste `ss` v `purchase()`, ale nie v `completePurchase()`, alebo naopak. Do oboch odovzdajte tú istú hodnotu.

## „The sharedSecret parameter is required“

Kľúč je `null`, prázdny alebo len z bielych znakov, zvyčajne preto, že v danom nasadení chýba premenná prostredia. Od verzie 4.0 brána odmieta podpisovať a overovať prázdnym kľúčom, ktorý vie zopakovať ktokoľvek. Nastavte zdieľaný kľúč od VÚB (alebo kľúč simulátora v testovacom režime).

## „The vs parameter must be exactly 10 digits when ss is set“

So špecifickým symbolom musí mať variabilný symbol plných 10 číslic, pretože VÚB podpisuje `VS + SS` bez oddeľovača. ID objednávky doplňte nulami, `str_pad((string) $id, 10, '0', STR_PAD_LEFT)`, túto hodnotu uložte k objednávke a tú istú odovzdajte do `purchase()` aj `completePurchase()`. Pozrite [Bezpečnosť](./guide/security#variable-and-specific-symbol).

## VÚB odmietne platbu so symbolmi zloženými z núl

Produkcia VÚB neprijme konštantný ani špecifický symbol zložený len z núl. Parameter radšej vynechajte.

## Existuje notifikácia zo servera na server?

Nie. E-platby zákazníka len presmerujú späť. Ak zákazník po zaplatení zatvorí prehliadač, váš e-shop sa o platbe dozvie z bankového výpisu. `REM` a `RSMS` zabezpečia, že banka upozorní zákazníka, nie vás.

## Môžem vrátiť platbu, zachytiť ju alebo sa spýtať na jej stav?

Nie. E-platby majú len platobné tlačidlo: `purchase()` a `completePurchase()`. Vrátenie peňazí sa robí bankovým prevodom.

## Aké meny sú podporované?

Len euro; požiadavka nemá pole pre menu.

## Môžem bránu používať bez HTTP klienta Omnipay?

Brána nerobí žiadne HTTP požiadavky, ale konštruktor Omnipay vyhľadáva PSR-18 klienta. Nainštalujte nejaký (`php-http/guzzle7-adapter`) alebo konštruktoru odovzdajte vlastný `Omnipay\Common\Http\ClientInterface`.

## Ako ju použijem v Laraveli, Symfony alebo Nette?

Bránu vytvárajte pre každú požiadavku a odovzdajte jej aktuálnu HTTP požiadavku (Symfony `Request`; požiadavka v Laraveli od neho dedí). V akcii pokladne zavolajte `redirect()` alebo vráťte `getRedirectResponse()`, v akcii návratu `completePurchase()`. Pozrite [Konfigurácia](./guide/configuration#the-http-request-in-frameworks).

## Prečo sú triedy `final`?

Aby sa kód podpisovania a overovania nedal omylom zmeniť v podtriede. Bránu namiesto dedenia obaľte vlastnou službou; pozrite [Prechod z 3.x](./upgrade#final-classes).
