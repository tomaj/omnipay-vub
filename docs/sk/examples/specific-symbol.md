# Špecifický symbol

Platba so špecifickým symbolom (`ss`): je súčasťou oboch podpisov, takže tá istá hodnota ide do `purchase()` aj do `completePurchase()`. Variabilný symbol je ID objednávky doplnené na 10 číslic: so špecifickým symbolom brána vyžaduje presne 10 číslic, aby sa `VS + SS`, ktoré banka podpisuje bez oddeľovača, dalo rozdeliť len jedným spôsobom.

**Ukazuje:** `ss` v podpisovanom reťazci, `getSs()`, `Specific symbol mismatch`, `Variable symbol mismatch` pri čísliciach presunutých medzi symbolmi, kratší variabilný symbol odmietnutý v `purchase()`.

<<< @/../examples/specific-symbol/run.php{php}

## Výstup {#output}

<<< @/../examples/specific-symbol/expected-output.txt{text}

## Poznámky {#notes}

- „ss not passed“: návrat nesie `SS=2024`, ale `completePurchase()` dostal pokyn žiadny neočakávať.
- „digits moved from VS to SS“: `000000471` + `12024` + `OK` je ten istý podpisovaný reťazec ako `0000004711` + `2024` + `OK`, takže podpis sedí, ale variabilný symbol nepatrí tejto objednávke. Pozrite [Bezpečnosť](../guide/security#variable-and-specific-symbol).
- „purchase() with VS=4711“: bez doplnenia nulami má variabilný symbol 4 číslice a `purchase()` (rovnako ako `completePurchase()`) vyhodí `InvalidRequestException('The vs parameter must be exactly 10 digits when ss is set')`.
- Simulátor platieb podpisuje špecifický symbol na inej pozícii ([payment-simulator#26](https://github.com/tomaj/payment-simulator/issues/26)); tam používajte platby bez `ss`.
