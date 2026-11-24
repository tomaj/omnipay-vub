# Priebeh platby

VÚB e-platby je platba s presmerovaním: váš server s bankou nikdy priamo nekomunikuje. Prehliadač zákazníka prenesie podpísanú požiadavku do VÚB a podpísanú odpoveď späť. Brána tú prvú zostaví a tú druhú overí.

<Diagram name="flow" :width="760" :height="512" alt="Sekvencia: zákazník zaplatí v e-shope; purchase() odpovie automaticky odosielaným formulárom s podpísanými poľami; prehliadač ho odošle do VÚB e-platby; zákazník zaplatí v internet bankingu; VÚB presmeruje prehliadač na návratovú URL s VS, SS, RES a SIGN; completePurchase() overí podpis a symboly" />

## Krok za krokom {#step-by-step}

1. **Pokladňa.** E-shop si uloží objednávku (sumu, variabilný symbol, prípadne špecifický symbol) a zavolá `purchase()`. Brána podpíše `MID + AMT + VS + SS + CS + RURL` zdieľaným kľúčom ([Podpisy](./signatures)).
2. **Presmerovanie.** `redirect()` pošle prehliadaču HTML stránku so skrytým formulárom, ktorý sa sám odošle na `https://ib.vub.sk/e-platbyeuro.aspx` (alebo na simulátor v [testovacom režime](./test-mode)). Z vášho servera do banky nejde nič.
3. **Platba.** VÚB overí podpis, zákazník sa prihlási do internet bankingu a platbu potvrdí alebo zruší.
4. **Návrat.** VÚB presmeruje prehliadač na vašu `RURL` s parametrami `VS`, `SS` (len ak ho platba mala), `RES` (`OK`, `FAIL` alebo `TOUT`) a `SIGN` v query stringu.
5. **Overenie.** Návratová URL zavolá `completePurchase()` s variabilným symbolom (a špecifickým symbolom) objednávky. Brána znova vypočíta `SIGN` z `VS + SS + RES`, porovná ho v konštantnom čase a skontroluje, že symboly patria tejto objednávke. Potom vám `isSuccessful()` povie, či je `RES` rovné `OK`.

## Čo vám návrat povie a čo nie {#what-the-return-tells-you-and-what-it-does-not}

| Dozviete sa | Nedozviete sa |
|---|---|
| že návrat vytvorila banka (`SIGN`) | sumu: banka ju neposiela ani nepodpisuje |
| ku ktorej objednávke patrí (`VS`, `SS`) | kedy bola platba zrealizovaná |
| výsledok: `OK`, `FAIL` alebo `TOUT` | účet platiteľa |

Suma preto vždy pochádza z vašej uloženej objednávky. Pre účtovníctvo platby párujte s bankovým výpisom.

## Čo sa v praxi stáva {#things-that-happen-in-practice}

- **Zákazník po zaplatení zatvorí prehliadač.** E-platby nemajú žiadnu notifikáciu zo servera na server, takže objednávka zostane vo vašom e-shope nezaplatená, kým ju nespárujete s bankovým výpisom. Takéto objednávky zobrazujte ako „čaká na platbu“, nerušte ich hneď.
- **Zákazník obnoví návratovú stránku** alebo sa na ňu vráti tlačidlom Späť. Rovnaký podpísaný návrat príde znova: označenie objednávky ako zaplatenej musí byť idempotentné ([príklad](../examples/return-url)).
- **Zákazník platbu v internet bankingu zruší:** `RES=FAIL`. Nechajte ho zaplatiť znova.
- **Banke vyprší čas platby:** `RES=TOUT`. Správajte sa k nemu ako k `FAIL`.
- **Niekto upraví query string.** Podpis potom nesedí a `completePurchase()` vyhodí výnimku ([Spracovanie chýb](./errors)).

[Príklad celého priebehu s presmerovaním](../examples/full-flow) to všetko spustí na localhoste s falošnou bankou.
