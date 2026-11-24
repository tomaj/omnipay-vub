# Specific symbol

A payment with a specific symbol (`ss`): it is part of both signatures, so the same value goes to `purchase()` and `completePurchase()`. The variable symbol is the order ID padded to 10 digits: with a specific symbol the gateway requires exactly 10 digits, so `VS + SS`, which the bank signs without a separator, can only be split one way.

**Shows:** `ss` in the signed string, `getSs()`, `Specific symbol mismatch`, `Variable symbol mismatch` for digits moved between the symbols, a shorter variable symbol rejected by `purchase()`.

<<< @/../examples/specific-symbol/run.php{php}

## Output

<<< @/../examples/specific-symbol/expected-output.txt{text}

## Notes

- "ss not passed": the return carries `SS=2024`, but `completePurchase()` was told to expect none.
- "digits moved from VS to SS": `000000471` + `12024` + `OK` is the same signed string as `0000004711` + `2024` + `OK`, so the signature matches, but the variable symbol is not the order's. See [Security](../guide/security#variable-and-specific-symbol).
- "purchase() with VS=4711": without the padding the variable symbol has 4 digits, and `purchase()` (as well as `completePurchase()`) throws `InvalidRequestException('The vs parameter must be exactly 10 digits when ss is set')`.
- The payment simulator signs the specific symbol in another position ([payment-simulator#26](https://github.com/tomaj/payment-simulator/issues/26)); use payments without `ss` there.
