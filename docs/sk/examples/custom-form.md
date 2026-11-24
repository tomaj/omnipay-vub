# Vlastné platobné tlačidlo

Namiesto stránky „Redirecting...“ z Omnipay ukážte zákazníkovi rekapituláciu objednávky a tlačidlo, ktoré odošle podpísaný formulár. Formulár sa zostaví z `getRedirectUrl()` a `getRedirectData()`; prázdne polia sa vynechajú a každá hodnota sa escapuje.

**Ukazuje:** vlastné vykreslenie formulára, `testHost` nasmerovaný na vlastný simulátor, pole pre SMS notifikáciu `rsms`.

<<< @/../examples/custom-form/run.php{php}

## Výstup {#output}

<<< @/../examples/custom-form/expected-output.txt{html}

## Poznámky {#notes}

- Vynechanie prázdnych polí podpis nemení: prázdne pole do podpisovaného reťazca nepridá nič.
- Zákazník nesmie môcť upraviť žiadne pole. Zmenený `AMT`, `VS` alebo `RURL` pokazí podpis a VÚB platbu odmietne, ako ukazuje [celý priebeh](./full-flow).
