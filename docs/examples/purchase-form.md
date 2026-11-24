# Payment form

The checkout side of a payment: build the signed request, look at every field the browser will post to VÚB, check the signature with the `openssl` command line, and produce the auto-submitting HTML page that `redirect()` sends.

**Shows:** `purchase()` parameters (`amount`, `vs`, `cs`, `rurl`, `rem`), the `PurchaseResponse` methods, the signed string `MID + AMT + VS + SS + CS + RURL`, `getRedirectResponse()`.

<<< @/../examples/purchase-form/run.php{php}

## Output

<<< @/../examples/purchase-form/expected-output.txt{text}

## Notes

- `SS` and `RSMS` were not set, so they are `null` and add nothing to the signed string; Omnipay's page still posts them as empty fields.
- `REM` (and `RSMS`) are posted but not signed.
- In a controller, call `$response->redirect()` and stop, or return `$response->getRedirectResponse()` from a framework action. See [Starting a payment](../guide/purchase).
