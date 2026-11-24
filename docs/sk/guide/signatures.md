# Podpisy

VÚB e-platby podpisujú cez HMAC-SHA256. Kľúčom je zdieľaný kľúč od VÚB, správou je spojenie polí bez oddeľovačov a podpisom je výsledný hash hexadecimálne veľkými písmenami. Prázdne nepovinné pole do správy nepridá nič.

## Platobná požiadavka: `SIGN` {#payment-request-sign}

```
SIGN = HMAC-SHA256(sharedSecret, MID + AMT + VS + SS + CS + RURL)
```

`REM` a `RSMS` sa posielajú, ale nepodpisujú. Pre MID `12345678`, sumu `10.00`, VS `2024000123`, bez SS, CS `0308`, RURL `https://shop.example/return` a kľúč simulátora (64 × `1`) je správa `1234567810.0020240001230308https://shop.example/return`:

```bash
printf '%s' '1234567810.0020240001230308https://shop.example/return' \
  | openssl dgst -sha256 -hmac 1111111111111111111111111111111111111111111111111111111111111111 \
  | awk '{print toupper($NF)}'
# 192E9B95242707C12828B2ABC0B483E8EB390A28A322893EC9CD1CC107E1CC90
```

To je `SIGN`, ktorý vypíše [príklad Platobný formulár](../examples/purchase-form), a ten ho tiež overí cez `openssl`.

## Návrat: `SIGN` {#return-sign}

Banka presmeruje na `RURL?VS=...&SS=...&RES=...&SIGN=...`:

```
SIGN = HMAC-SHA256(sharedSecret, VS + SS + RES)
```

```bash
printf '%s' '2024000123OK' \
  | openssl dgst -sha256 -hmac 1111111111111111111111111111111111111111111111111111111111111111 \
  | awk '{print toupper($NF)}'
# 6ADD04387C7A8787B51C1A5E48C546F66335BCF3DF18E93735A618470609C87F
```

`completePurchase()` vypočíta podpis z prijatých `VS`, `SS` a `RES` a porovná ho so `SIGN` cez `hash_equals()`, ktoré trvá rovnako dlho bez ohľadu na to, kde sa reťazce líšia. Porovnanie rozlišuje veľké a malé písmená: banka posiela hex veľkými písmenami.

::: danger Čo podpis návratu nepokrýva
Návrat je podpísaný cez variabilný symbol, špecifický symbol a výsledok. **Suma podpísaná nie je** a v návrate sa ani nenachádza. Sumu berte z uloženej objednávky. Pozrite [Bezpečnosť](./security).
:::

## Trieda `HmacSign` {#the-hmacsign-class}

```php
use Omnipay\Eplatby\Sign\HmacSign;

echo new HmacSign()->sign('2024000123OK', str_repeat('1', 64)) . "\n";
```

<!-- snippet: output -->
```text
6ADD04387C7A8787B51C1A5E48C546F66335BCF3DF18E93735A618470609C87F
```

`HmacSign::sign(string $input, string $secret): string` je `strtoupper(hash_hmac('sha256', $input, $secret))`. `PurchaseRequest::generateSignature(string $data)` ju volá so zdieľaným kľúčom (`sharedSecret`) požiadavky.

## História: kam patrí špecifický symbol {#history-where-the-specific-symbol-goes}

omnipay-vub 3.x posielal `SS`, ale vynechával ho z oboch podpisov, takže VÚB platby so špecifickým symbolom odmietala. Poradie `MID + AMT + VS + SS + CS + RURL` a návrat `VS + SS + RES` si s VÚB overil jeden z prispievateľov ([#2](https://github.com/tomaj/omnipay-vub/pull/2)) a používajú sa od verzie 4.0. Platby bez špecifického symbolu sa podpisujú vo všetkých verziách rovnako.
