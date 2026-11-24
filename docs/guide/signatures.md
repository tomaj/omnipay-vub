# Signatures

VÚB e-platby signs with HMAC-SHA256. The key is the shared secret from VÚB, the message is a concatenation of fields without separators, and the signature is the digest in uppercase hexadecimal. An empty optional field adds nothing to the message.

## Payment request: `SIGN`

```
SIGN = HMAC-SHA256(sharedSecret, MID + AMT + VS + SS + CS + RURL)
```

`REM` and `RSMS` are sent but not signed. With MID `12345678`, amount `10.00`, VS `2024000123`, no SS, CS `0308`, RURL `https://shop.example/return` and the simulator's key (64 × `1`), the message is `1234567810.0020240001230308https://shop.example/return`:

```bash
printf '%s' '1234567810.0020240001230308https://shop.example/return' \
  | openssl dgst -sha256 -hmac 1111111111111111111111111111111111111111111111111111111111111111 \
  | awk '{print toupper($NF)}'
# 192E9B95242707C12828B2ABC0B483E8EB390A28A322893EC9CD1CC107E1CC90
```

That is the `SIGN` the [Payment form example](../examples/purchase-form) prints, and it checks it with `openssl` too.

## Return: `SIGN`

The bank redirects to `RURL?VS=...&SS=...&RES=...&SIGN=...`:

```
SIGN = HMAC-SHA256(sharedSecret, VS + SS + RES)
```

```bash
printf '%s' '2024000123OK' \
  | openssl dgst -sha256 -hmac 1111111111111111111111111111111111111111111111111111111111111111 \
  | awk '{print toupper($NF)}'
# 6ADD04387C7A8787B51C1A5E48C546F66335BCF3DF18E93735A618470609C87F
```

`completePurchase()` computes the signature from the received `VS`, `SS` and `RES` and compares it with `SIGN` using `hash_equals()`, which takes the same time wherever the strings differ. The comparison is case-sensitive: the bank sends uppercase hex.

::: danger What the return signature does not cover
The return is signed over the variable symbol, the specific symbol and the result. **The amount is not signed**, and it is not even in the return. Take the amount from your stored order. See [Security](./security).
:::

## The `HmacSign` class

```php
use Omnipay\Eplatby\Sign\HmacSign;

echo new HmacSign()->sign('2024000123OK', str_repeat('1', 64)) . "\n";
```

<!-- snippet: output -->
```text
6ADD04387C7A8787B51C1A5E48C546F66335BCF3DF18E93735A618470609C87F
```

`HmacSign::sign(string $input, string $secret): string` is `strtoupper(hash_hmac('sha256', $input, $secret))`. `PurchaseRequest::generateSignature(string $data)` calls it with the request's `sharedSecret`.

## History: where the specific symbol goes

omnipay-vub 3.x sent `SS` but left it out of both signatures, so VÚB rejected payments with a specific symbol. The order `MID + AMT + VS + SS + CS + RURL` and the return `VS + SS + RES` were confirmed with VÚB by a contributor ([#2](https://github.com/tomaj/omnipay-vub/pull/2)) and are used since 4.0. Payments without a specific symbol sign the same in every version.
