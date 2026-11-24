<?php

declare(strict_types=1);

// Checkout: build the signed payment request and the auto-submitting form that sends the customer to VÚB.
// The signature is then recomputed with the openssl CLI, independently of the library.

use Omnipay\Eplatby\Gateway;

require __DIR__ . '/vendor/autoload.php';

$gateway = new Gateway();
$gateway->setMid('12345678');                     // your MID from VÚB
$gateway->setSharedSecret(str_repeat('1', 64));   // the payment simulator's key; load yours from the environment
$gateway->setTestMode(true);                      // the payment simulator instead of ib.vub.sk

$response = $gateway->purchase([
    'amount' => '10.00',
    'vs' => '2024000123',                         // variable symbol: unique per order, up to 10 digits
    'cs' => '0308',                               // constant symbol (optional)
    'rurl' => 'https://shop.example/return',      // where the bank sends the customer back
    'rem' => 'customer@example.com',              // e-mail notification from the bank (optional, not signed)
])->send();

echo 'isRedirect:   ' . var_export($response->isRedirect(), true) . "\n";
echo 'isSuccessful: ' . var_export($response->isSuccessful(), true) . "\n";
echo 'method:       ' . $response->getRedirectMethod() . "\n";
echo 'url:          ' . $response->getRedirectUrl() . "\n";
echo "fields:\n";
foreach ($response->getRedirectData() as $name => $value) {
    printf("  %-4s = %s\n", $name, var_export($value, true));
}

// SIGN = HMAC-SHA256(sharedSecret, MID + AMT + VS + SS + CS + RURL), uppercase hex; empty fields add nothing
$data = $response->getRedirectData();
$signed = '12345678' . '10.00' . '2024000123' . '' . '0308' . 'https://shop.example/return';
$openssl = proc_open(['openssl', 'dgst', '-sha256', '-hmac', str_repeat('1', 64)], [['pipe', 'r'], ['pipe', 'w']], $pipes);
if ($openssl === false) {
    throw new RuntimeException('openssl is not installed');
}
fwrite($pipes[0], $signed);
fclose($pipes[0]);
$digest = (string) stream_get_contents($pipes[1]);
fclose($pipes[1]);
proc_close($openssl);
$expected = strtoupper(trim(substr($digest, (int) strrpos($digest, ' '))));
echo "\nsigned string: {$signed}\n";
echo 'openssl:       ' . $expected . "\n";
echo 'SIGN matches:  ' . var_export($data['SIGN'] === $expected, true) . "\n";

// In the checkout controller you would now send the auto-submitting form to the browser:
//     $response->redirect();
// Here it is captured to show what the customer's browser receives.
echo "\nHTML sent to the browser:\n";
echo $response->getRedirectResponse()->getContent() . "\n";
