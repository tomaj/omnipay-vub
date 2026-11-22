<?php

declare(strict_types=1);

// Return URL (RURL): the bank redirects the customer here with VS, SS, RES and SIGN in the query.

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Eplatby\Gateway;

require __DIR__ . '/../vendor/autoload.php';

$gateway = new Gateway(); // reads the query through Request::createFromGlobals()
$gateway->setSharedSecret(str_repeat('1', 64));   // the same key as in purchase.php

// Your orders, normally a database table. Store the amount and the symbols you sent to the bank.
$orders = [
    '2024000123' => ['amount' => '10.00', 'ss' => null, 'paid' => false],
];

$vs = $_GET['VS'] ?? null;
$order = is_string($vs) ? $orders[$vs] ?? null : null;
if (!is_string($vs) || $order === null) {
    http_response_code(404);
    echo "Unknown order\n";
    return;
}

try {
    // verifies SIGN (VS + SS + RES) and that VS and SS are the ones of this order
    $response = $gateway->completePurchase(['vs' => $vs, 'ss' => $order['ss']])->send();
} catch (InvalidRequestException $e) {
    // tampered or foreign return: never mark the order paid
    http_response_code(400);
    echo 'Invalid return: ' . $e->getMessage() . "\n";
    return;
}

if ($response->isSuccessful()) {
    // RES=OK. The bank does not sign the amount: the paid amount is the one stored with the order.
    $order['paid'] = true;
    echo "Order {$vs} paid, {$order['amount']} EUR\n";
} else {
    // RES=FAIL (rejected or cancelled) or RES=TOUT (timeout)
    echo "Order {$vs} not paid: {$response->getRes()}\n";
}
