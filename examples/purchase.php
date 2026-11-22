<?php

declare(strict_types=1);

// Checkout: redirect the customer to VÚB e-platby.
// Run: php -S 127.0.0.1:8000 -t examples, then open http://127.0.0.1:8000/purchase.php

use Omnipay\Eplatby\Gateway;

require __DIR__ . '/../vendor/autoload.php';

$gateway = new Gateway();
$gateway->setMid('12345678');                     // your MID from VÚB
$gateway->setSharedSecret(str_repeat('1', 64));   // the simulator's key; load yours from the environment
$gateway->setTestMode(true);                      // payment simulator instead of ib.vub.sk

$response = $gateway->purchase([
    'amount' => '10.00',
    'vs' => '2024000123',          // variable symbol: unique per order, up to 10 digits
    'cs' => '0308',                // constant symbol (optional)
    'rurl' => 'https://shop.example/return.php',
])->send();

// an auto-submitting HTML form that POSTs MID, AMT, VS, CS, RURL, SIGN, ... to the bank
$response->redirect();
