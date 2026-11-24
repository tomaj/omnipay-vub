# Návratová URL

Controller návratovej URL spustený proti návratom, ktoré e-shop v praxi vidí. `bankReturn()` hrá úlohu banky a podpisuje cez `hash_hmac()`; `handleReturn()` je kód, ktorý by ste dali do svojho controllera.

**Ukazuje:** `completePurchase()` s očakávaným `vs` a `ss`, odovzdanie aktuálnej Symfony požiadavky bráne, každú kontrolu a čo na ňu odpovedať, idempotentné označenie „zaplatené“.

<<< @/../examples/return-url/run.php{php}

## Výstup {#output}

<<< @/../examples/return-url/expected-output.txt{text}

## Čo testuje každý prípad {#what-each-case-tests}

| Prípad | Prečo skončí takto |
|---|---|
| cancelled by the customer (zrušené zákazníkom) | pravý návrat s `RES=FAIL`: `isSuccessful()` je `false` |
| timed out (vypršal čas) | pravý návrat s `RES=TOUT` |
| RES changed to OK (RES zmenený na OK) | `RES` je podpísaný: podpis už nesedí |
| return of another order (návrat inej objednávky) | pravý návrat `OK`, ale pre `VS=2024000999`: `Variable symbol mismatch` |
| return URL opened directly (návratová URL otvorená priamo) | žiadny `SIGN` |
| paid (zaplatené) | pravý návrat `OK` pre túto objednávku |
| paid, page reloaded (zaplatené, stránka obnovená) | ten istý návrat znova: handler označí objednávku ako zaplatenú len raz |

Suma objednávky (`10.00`) pochádza z objednávky, nikdy nie z požiadavky: VÚB ju nepodpisuje.
