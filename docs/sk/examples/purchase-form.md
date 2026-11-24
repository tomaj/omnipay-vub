# Platobný formulár

Strana pokladne: zostavenie podpísanej požiadavky, prehľad každého poľa, ktoré prehliadač odošle do VÚB, overenie podpisu cez príkazový riadok `openssl` a vytvorenie automaticky odosielanej HTML stránky, ktorú pošle `redirect()`.

**Ukazuje:** parametre `purchase()` (`amount`, `vs`, `cs`, `rurl`, `rem`), metódy `PurchaseResponse`, podpisovaný reťazec `MID + AMT + VS + SS + CS + RURL`, `getRedirectResponse()`.

<<< @/../examples/purchase-form/run.php{php}

## Výstup {#output}

<<< @/../examples/purchase-form/expected-output.txt{text}

## Poznámky {#notes}

- `SS` a `RSMS` neboli nastavené, takže sú `null` a do podpisovaného reťazca nepridajú nič; stránka z Omnipay ich aj tak odošle ako prázdne polia.
- `REM` (a `RSMS`) sa odosielajú, ale nepodpisujú.
- V controlleri zavolajte `$response->redirect()` a skončite, alebo vo frameworku vráťte z akcie `$response->getRedirectResponse()`. Pozrite [Začatie platby](../guide/purchase).
