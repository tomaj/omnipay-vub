# Return URL

The return URL controller, run against the returns a shop sees in practice. `bankReturn()` plays the bank and signs with `hash_hmac()`; `handleReturn()` is the code you would put in your controller.

**Shows:** `completePurchase()` with the expected `vs` and `ss`, passing the current Symfony request to the gateway, every check and what to answer, idempotent "mark as paid".

<<< @/../examples/return-url/run.php{php}

## Output

<<< @/../examples/return-url/expected-output.txt{text}

## What each case tests

| Case | Why it ends like that |
|---|---|
| cancelled by the customer | genuine return with `RES=FAIL`: `isSuccessful()` is `false` |
| timed out | genuine return with `RES=TOUT` |
| RES changed to OK | `RES` is signed: the signature no longer matches |
| return of another order | a genuine `OK` return, but for `VS=2024000999`: `Variable symbol mismatch` |
| return URL opened directly | no `SIGN` at all |
| paid | genuine `OK` return for this order |
| paid, page reloaded | the same return again: the handler marks the order paid only once |

The order's amount (`10.00`) comes from the order, never from the request: VÚB does not sign it.
