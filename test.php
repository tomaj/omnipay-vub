<?php

use Omnipay\Omnipay;

require_once __DIR__ . '/vendor/autoload.php';

$gateway = Omnipay::create('Eplatby');

$vubSimulation = false;
if ($vubSimulation) {
    $mid = 'testHMAC';
    $sharedSecret = '36fmtvHtjyRAieZ9k+x9zldZIYhSx/vh22iaBG9n1aOd1ncFKrUCeR7gAE5Dg2D1';
} else {
    $mid = '11111111';
    $sharedSecret = '1111111111111111111111111111111111111111111111111111111111111111';
}

$gateway->setMid($mid);
$gateway->setSharedSecret($sharedSecret);
$gateway->setTestMode(true);

$response = $gateway->purchase([
    'amount' => '10.00',
    'VS' => '123456',
    'CS' => '0321',
    'SS' => '1234',
    'rurl' => 'http://localhost:4444/testserver.php',
])->send();

if ($response->isSuccessful()) {
    
    // Payment was successful
    print_r($response);

} elseif ($response->isRedirect()) {
    
    // Redirect to offsite payment gateway
    // echo($response->getRedirectUrl() . "\n");

    $response->redirect();

} else {

    // Payment failed
    echo $response->getMessage();
}


