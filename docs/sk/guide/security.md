# Bezpečnosť

omnipay-vub podpisuje vaše platobné požiadavky a overuje návraty z banky. Táto stránka popisuje, pred čím vás to chráni, pred čím nie a čo zostáva na vašom e-shope. Zraniteľnosť nahláste podľa [bezpečnostnej politiky](https://github.com/tomaj/omnipay-vub/security/policy): súkromne, nie vo verejnom issue.

## Zdieľaný kľúč {#the-shared-secret}

- Zdieľaný kľúč (`sharedSecret`) podpisuje požiadavky a overuje návraty. Kto ho má, dokáže podvrhnúť návrat `OK`. Držte ho v premenných prostredia alebo v úložisku tajomstiev; necommitujte ho, nelogujte ho a neposielajte ho do prehliadača.
- Prázdny kľúč alebo kľúč len z bielych znakov brána odmietne: `purchase()` aj `completePurchase()` vyhodia `InvalidRequestException('The sharedSecret parameter is required')` namiesto toho, aby podpisovali prázdnym HMAC kľúčom, ktorý vie zopakovať ktokoľvek. Konfiguráciu aj tak skontrolujte pri štarte aplikácie, aby chýbajúci kľúč zastavil nasadenie, a nie až prvú platbu.
- Kľúč simulátora (64 × `1`) je verejný. Používajte ho len v testovacom režime.

### Výpisy zásobníka {#stack-traces}

Chybové hlásenia (Tracy, Sentry, Monolog, `getTraceAsString()`) ukazujú argumenty každého volania na zásobníku. Parametre, ktoré preberajú kľúč, sú označené atribútom `#[\SensitiveParameter]`, takže PHP namiesto kľúča vypíše `Object(SensitiveParameterValue)`: `setSharedSecret()` v triedach `Gateway`, `PurchaseRequest` a `CompletePurchaseRequest` a `HmacSign::sign()`.

Na polia sa to nevzťahuje: kľúč odovzdaný cez `initialize(['sharedSecret' => ...])` alebo `purchase(['sharedSecret' => ...])` sa vypíše spolu s poľom. Nastavte ho cez `setSharedSecret()` a v produkcii vypnite argumenty vo výpisoch nastavením `zend.exception_ignore_args=On` v `php.ini` (predvolená hodnota v `php.ini-production`).

## Čo `completePurchase()` zaručuje {#what-completepurchase-guarantees}

Keď skončí bez výnimky:

- tieto `VS`, `SS` a `RES` vytvorila banka (alebo ten, kto má zdieľaný kľúč); podpis bol porovnaný v konštantnom čase (`hash_equals()`);
- návrat patrí k variabilnému symbolu, ktorý ste odovzdali, a k špecifickému symbolu, ktorý ste odovzdali (alebo k žiadnemu).

## Čo zaručiť nemôže {#what-it-cannot-guarantee}

- **Sumu.** VÚB sumu v návrate neposiela ani nepodpisuje. Suma je tá, ktorá je uložená pri objednávke s variabilným symbolom, ktorý ste odovzdali. Sumu, cenu ani celkovú hodnotu objednávky nikdy nečítajte z požiadavky.
- **Čerstvosť.** Návrat nemá časovú pečiatku ani nonce; tá istá URL sa overí znova a znova. Označenie „zaplatené“ musí byť idempotentné a vedľajšie efekty (odoslanie tovaru, e-maily) sa nesmú spustiť dvakrát.
- **Že návrat vôbec príde.** Zákazník môže po zaplatení zatvoriť prehliadač. Nezaplatené objednávky párujte s bankovým výpisom.

## Variabilný a špecifický symbol {#variable-and-specific-symbol}

Banka podpisuje `VS + SS + RES` bez oddeľovača, takže `VS=12345` so `SS=6` podpíše ten istý reťazec ako `VS=123456` bez `SS`. Brána to rieši v dvoch krokoch:

- `completePurchase()` porovná oba symboly so symbolmi vašej objednávky (`ss` rovné `null` znamená „žiadny `SS`“), takže návrat, v ktorom sa číslice presunuli medzi `VS` a `SS`, túto objednávku nedokončí;
- so špecifickým symbolom vyžadujú `purchase()` aj `completePurchase()` variabilný symbol s presne 10 číslicami, čo je maximum, ktoré VÚB povoľuje. Podpísaný `VS + SS` takejto objednávky sa dá rozdeliť len jedným spôsobom a nemôže sa rovnať `VS` objednávky bez špecifického symbolu (ten má najviac 10 číslic). ID objednávky doplňte nulami: `str_pad((string) $id, 10, '0', STR_PAD_LEFT)`.

Bez špecifických symbolov sa nič nemení: každá objednávka očakáva prázdny `SS`, takže návrat so `SS` sa odmietne. Variabilné symboly jednej pevnej dĺžky (10 číslic) aj tak odporúčame pre všetky objednávky.

## Testovací režim {#test-mode}

Testovací režim mení len to, kam sa formulár odošle. Verejný kľúč simulátora nikdy nepoužívajte v produkcii a `testMode` aj kľúč držte v tej istej konfigurácii prostredia.

## Prenos {#transport}

Pokladňu aj návratovú URL prevádzkujte cez HTTPS. Návrat nenesie žiadne tajomstvo, ale cez obyčajné HTTP by ho ktokoľvek v sieti mohol vidieť a zopakovať.

## Nahlasovanie {#reporting}

Zraniteľnosti nahlasujte cez [súkromné nahlásenie zraniteľnosti na GitHube](https://github.com/tomaj/omnipay-vub/security/advisories/new). [Bezpečnostná politika](https://github.com/tomaj/omnipay-vub/security/policy) uvádza podporované verzie.
