<?php

declare(strict_types=1);

// A payment with a specific symbol (SS): it is part of both signatures, so pass the same ss to
// purchase() and completePurchase(). Variable symbols are zero-padded to one fixed length (10 digits),
// so VS + SS, which the bank signs without a separator, can only be split one way.

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Eplatby\Gateway;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/vendor/autoload.php';

$sharedSecret = str_repeat('1', 64);   // the payment simulator's key

$orderId = 4711;
$order = [
    'vs' => str_pad((string) $orderId, 10, '0', STR_PAD_LEFT),   // '0000004711'
    'ss' => '2024',                                              // e.g. the invoice year your accounting wants
    'amount' => '49.00',
];

$gateway = new Gateway();
$gateway->setMid('12345678');
$gateway->setSharedSecret($sharedSecret);
$gateway->setTestMode(true);

$request = $gateway->purchase([
    'amount' => $order['amount'],
    'vs' => $order['vs'],
    'ss' => $order['ss'],
    'rurl' => 'https://shop.example/return',
]);
$data = $request->send()->getRedirectData();

echo "purchase(): VS={$request->getVs()} SS={$request->getSs()}\n";
// MID + AMT + VS + SS + CS + RURL; CS was not sent, so it adds nothing
$signed = $request->getMid() . $request->getAmount() . $request->getVs() . $request->getSs() . $request->getCs() . $request->getRurl();
echo "  signed: MID + AMT + VS + SS + CS + RURL = {$signed}\n";
echo '  SIGN matches hash_hmac(): ' . var_export($data['SIGN'] === strtoupper(hash_hmac('sha256', $signed, $sharedSecret)), true) . "\n\n";

/**
 * The bank's return: SIGN = HMAC-SHA256(secret, VS + SS + RES).
 *
 * @return array<string, string>
 */
$bankReturn = static fn(string $vs, string $ss, string $res): array => ['VS' => $vs, 'SS' => $ss, 'RES' => $res, 'SIGN' => strtoupper(hash_hmac('sha256', $vs . $ss . $res, $sharedSecret))];

/**
 * @param array<string, string> $query
 */
$complete = static function (array $query, string $vs, ?string $ss) use ($sharedSecret): string {
    $gateway = new Gateway(null, Request::create('https://shop.example/return', 'GET', $query));
    $gateway->setSharedSecret($sharedSecret);
    try {
        $response = $gateway->completePurchase(['vs' => $vs, 'ss' => $ss])->send();
    } catch (InvalidRequestException $e) {
        return 'rejected: ' . $e->getMessage();
    }

    return "RES={$response->getRes()} VS={$response->getVs()} SS={$response->getSs()} paid=" . var_export($response->isSuccessful(), true);
};

echo "completePurchase():\n";
printf("  %-28s %s\n", 'with the same ss:', $complete($bankReturn($order['vs'], $order['ss'], 'OK'), $order['vs'], $order['ss']));
printf("  %-28s %s\n", 'ss not passed:', $complete($bankReturn($order['vs'], $order['ss'], 'OK'), $order['vs'], null));
printf("  %-28s %s\n", 'return with another SS:', $complete($bankReturn($order['vs'], '2025', 'OK'), $order['vs'], $order['ss']));
// a digit moved from VS to SS signs the same string, but the symbols are not this order's
printf("  %-28s %s\n", 'digits moved from VS to SS:', $complete(['VS' => '000000471', 'SS' => '12024'] + $bankReturn($order['vs'], $order['ss'], 'OK'), $order['vs'], $order['ss']));
