# The payment flow

VÚB e-platby is a redirect payment: your server never talks to the bank. The customer's browser carries a signed request to VÚB and a signed answer back. The gateway builds the first and verifies the second.

<Diagram name="flow" :width="760" :height="512" alt="Sequence: the customer pays in the shop; purchase() answers with an auto-submitting form carrying the signed fields; the browser posts it to VÚB e-platby; the customer pays in internet banking; VÚB redirects the browser to the return URL with VS, SS, RES and SIGN; completePurchase() verifies the signature and the symbols" />

## Step by step

1. **Checkout.** Your shop stores the order (amount, variable symbol, specific symbol if any) and calls `purchase()`. The gateway signs `MID + AMT + VS + SS + CS + RURL` with the shared secret ([Signatures](./signatures)).
2. **Redirect.** `redirect()` sends the browser an HTML page with a hidden form that posts itself to `https://ib.vub.sk/e-platbyeuro.aspx` (or the simulator in [test mode](./test-mode)). Nothing goes from your server to the bank.
3. **Payment.** VÚB checks the signature, the customer signs in to internet banking and confirms or cancels the payment.
4. **Return.** VÚB redirects the browser to your `RURL` with `VS`, `SS` (only when the payment had one), `RES` (`OK`, `FAIL` or `TOUT`) and `SIGN` in the query string.
5. **Verification.** Your return URL calls `completePurchase()` with the variable symbol (and the specific symbol) of the order. It recomputes `SIGN` over `VS + SS + RES`, compares it in constant time and checks that the symbols are the ones of this order. Then `isSuccessful()` tells you whether `RES` is `OK`.

## What the return tells you, and what it does not

| You learn | You do not learn |
|---|---|
| that the bank produced the return (`SIGN`) | the amount: it is neither sent nor signed |
| which order it is for (`VS`, `SS`) | when the payment was made |
| the result: `OK`, `FAIL` or `TOUT` | the payer's account |

So the amount always comes from your stored order. Reconcile with the bank statement for accounting.

## Things that happen in practice

- **The customer closes the browser** after paying. There is no server-to-server notification in e-platby, so the order stays unpaid in your shop until you check the bank statement. Show such orders as "waiting for payment" rather than cancelling them right away.
- **The customer reloads the return page** or comes back with the back button. The same signed return arrives again: make marking the order paid idempotent ([example](../examples/return-url)).
- **The customer cancels** in internet banking: `RES=FAIL`. Let them try again.
- **The bank times out** the payment: `RES=TOUT`. Treat it like `FAIL`.
- **Somebody edits the query string.** The signature no longer matches and `completePurchase()` throws ([Error handling](./errors)).

The [full redirect flow example](../examples/full-flow) runs all of this on localhost with a fake bank.
