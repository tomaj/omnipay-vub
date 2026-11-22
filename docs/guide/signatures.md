# Signatures

VÚB e-platby uses HMAC-SHA256 with the shared secret from VÚB as the key. The signature is the uppercase hexadecimal digest. Empty optional fields add nothing to the signed string.

## Payment request

```
SIGN = HMAC-SHA256(sharedSecret, MID + AMT + VS + SS + CS + RURL)
```

`REM` and `RSMS` are sent but not signed. Example with MID `12345678`, amount `10.00`, VS `2024000123`, no SS, CS `0308` and RURL `https://shop.example/return.php`, using the simulator's key (64 × `1`):

```bash
printf '%s' '1234567810.0020240001230308https://shop.example/return.php' \
  | openssl dgst -sha256 -hmac 1111111111111111111111111111111111111111111111111111111111111111
# C45FB95A0136DC2DF289920255B955FCB38CC03B777AA26D34393BF7F3926F68 (uppercased)
```

## Return

The bank redirects to `RURL?VS=...&SS=...&RES=...&SIGN=...` (`SS` only when the payment had one):

```
SIGN = HMAC-SHA256(sharedSecret, VS + SS + RES)
```

`completePurchase()` computes it from the received values and compares it with `SIGN` using `hash_equals()`.

::: danger What the return signature does not cover
The return is signed over the variable symbol, the specific symbol and the result only. **The amount is not signed** and is not even part of the return, so you cannot verify from the return how much the customer paid. Take the amount from your stored order, and use a variable symbol that is unique per order.
:::

Because `VS` and `SS` are concatenated without a separator, `VS=12345&SS=6` signs the same string as `VS=123456` without `SS`. That is why `completePurchase()` checks both symbols against the expected ones, not just the signature.

## Where the specific symbol goes

omnipay-vub 3.x sent `SS` but left it out of both signatures, so VÚB rejected payments with a specific symbol. The order `VS + SS + CS` and the return `VS + SS + RES` were confirmed with VÚB by a contributor ([#2](https://github.com/tomaj/omnipay-vub/pull/2)) and are used since 4.0. Payments without a specific symbol sign the same in every version.

## The `HmacSign` class

```php
use Omnipay\Eplatby\Sign\HmacSign;

$sign = new HmacSign()->sign('2024000123OK', $sharedSecret);
```

`PurchaseRequest::generateSignature(string $data)` does the same with the request's `sharedSecret`.
