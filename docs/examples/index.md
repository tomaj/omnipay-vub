# Examples

Every example is a small Composer project in [`examples/`](https://github.com/tomaj/omnipay-vub/tree/master/examples) with the code shown on its page and an `expected-output.txt`. CI installs each one against the code of the pull request and fails when the output changes, so what you read here is what the library does. They run offline: the bank's side is played by a few lines of PHP that sign with `hash_hmac()` (and the `openssl` command line), independently of the library.

| Example | What it shows | Runs |
|---|---|---|
| [Payment form](./purchase-form) | `purchase()`, every field of the signed form, the signature recomputed with `openssl`, the auto-submitting page | `php run.php` |
| [Your own pay button](./custom-form) | rendering the form in your template from `getRedirectUrl()` / `getRedirectData()`, `testHost`, `rsms` | `php run.php` |
| [Return URL](./return-url) | `completePurchase()` in a controller: paid, cancelled, timed out, edited, another order's return, no parameters, reload | `php run.php` |
| [Specific symbol](./specific-symbol) | a payment with `ss`, fixed-length variable symbols, the symbol checks | `php run.php` |
| [Full redirect flow](./full-flow) | a shop and a fake bank on `php -S`, a script as the browser: pay, cancel, edited amount, forged return | `bash e2e.sh` |

## Run one

```bash
git clone https://github.com/tomaj/omnipay-vub.git
cd omnipay-vub/examples/return-url
composer install
php run.php            # or: bash e2e.sh in examples/full-flow
```

`examples/ci/run.sh <name>` does what CI does: copies the example to a temporary directory, installs it against your checkout (a Composer path repository) and compares the output with `expected-output.txt`.

The examples use the payment simulator's shared secret (64 × `1`) and made-up merchant data. In your shop, load the MID and the secret from the environment.
