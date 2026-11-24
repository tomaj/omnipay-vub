# Testing your integration

e-platby has no server-to-server calls, so everything the bank does can be reproduced in a test: a payment request is a signed form, a return is a signed query string. You need the shared secret of your test configuration and `hash_hmac()`.

## Unit test the return URL

Create the bank's return as a Symfony request and give it to the gateway. Sign it in the test with `hash_hmac()` directly, not with the library, so the test checks the library instead of repeating it:

```php
use Omnipay\Eplatby\Gateway;
use Symfony\Component\HttpFoundation\Request;

$secret = str_repeat('1', 64);
$bankReturn = static function (string $vs, string $res, string $ss = '') use ($secret): Request {
    $query = ['VS' => $vs, 'RES' => $res, 'SIGN' => strtoupper(hash_hmac('sha256', $vs . $ss . $res, $secret))];
    if ($ss !== '') {
        $query['SS'] = $ss;
    }

    return Request::create('https://shop.example/return', 'GET', $query);
};

$gateway = new Gateway(null, $bankReturn('2024000123', 'OK'));
$gateway->setSharedSecret($secret);
$response = $gateway->completePurchase(['vs' => '2024000123'])->send();

echo var_export($response->isSuccessful(), true) . ' ' . $response->getRes() . "\n";
```

<!-- snippet: output -->
```text
true OK
```

Cover at least: `OK`, `FAIL`, `TOUT`, an edited `RES`, the return of another order, the return URL without parameters, and the same `OK` return twice. The [Return URL example](../examples/return-url) runs exactly these cases through one handler.

## Unit test the checkout

Assert on `getRedirectUrl()` and `getRedirectData()`, and compute the expected `SIGN` independently, once, with the `openssl` command line ([Signatures](./signatures)). A test vector that the library computed itself would not catch a wrong field order.

```php
$data = $gateway->purchase(['amount' => '10.00', 'vs' => '2024000123', 'cs' => '0308', 'rurl' => 'https://shop.example/return'])->send()->getRedirectData();

// openssl dgst -sha256 -hmac 1111...1 of '1234567810.0020240001230308https://shop.example/return'
echo $data['SIGN'] === '192E9B95242707C12828B2ABC0B483E8EB390A28A322893EC9CD1CC107E1CC90' ? "signature ok\n" : "signature changed\n";
```

<!-- snippet: output -->
```text
signature ok
```

## End to end without a bank

The [Full redirect flow example](../examples/full-flow) starts your shop and a 40-line fake bank with `php -S`, and a script plays the browser: open the checkout, post the form, follow the redirect back. It runs in CI in a few seconds, offline. Copy `bank/index.php` into your own end-to-end tests and point `testHost` at it.

## Manual test with the simulator

Turn on [test mode](./test-mode) with the simulator's key and pay in the browser: `https://platby.tomaj.sk` (or your own instance) shows the request it received, whether the signature matches, and links back to your return URL with each result.

## Before going live

- [ ] the production MID and shared secret come from the environment, and test mode is off;
- [ ] the return URL is reachable over HTTPS from the customer's browser (the bank only redirects, it does not call it);
- [ ] a real payment of a small amount reaches `isSuccessful()` and marks the order paid once;
- [ ] a cancelled payment (`FAIL`) leaves the order unpaid and lets the customer pay again;
- [ ] orders whose customer never came back are checked against the bank statement.
