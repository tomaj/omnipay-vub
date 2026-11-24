# Your own pay button

Instead of Omnipay's "Redirecting..." page, show the customer the order summary and a button that posts the signed form. The form is built from `getRedirectUrl()` and `getRedirectData()`; empty fields are left out, and every value is escaped.

**Shows:** rendering the form yourself, `testHost` pointing at your own simulator, the SMS notification field `rsms`.

<<< @/../examples/custom-form/run.php{php}

## Output

<<< @/../examples/custom-form/expected-output.txt{html}

## Notes

- Leaving out empty fields does not change the signature: an empty field adds nothing to the signed string.
- Do not let the customer edit any of the fields. A changed `AMT`, `VS` or `RURL` breaks the signature and VÚB refuses the payment, as the [full flow](./full-flow) shows.
