<?php

declare(strict_types=1);

// Return URL (RURL): VÚB redirects the customer back with VS, SS, RES and SIGN in the query string.
// This runs the handler against the returns a shop sees in practice. The bank's side is played by
// bankReturn(), which signs with hash_hmac() directly, independently of the library.

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Eplatby\Gateway;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/vendor/autoload.php';

const SHARED_SECRET = '1111111111111111111111111111111111111111111111111111111111111111';   // the payment simulator's key

/**
 * What VÚB appends to RURL: SIGN = HMAC-SHA256(secret, VS + SS + RES), uppercase hex.
 *
 * @return array<string, string>
 */
function bankReturn(string $vs, string $res, string $ss = ''): array
{
    $query = ['VS' => $vs, 'SS' => $ss, 'RES' => $res];
    $query['SIGN'] = strtoupper(hash_hmac('sha256', $vs . $ss . $res, SHARED_SECRET));

    return $ss === '' ? array_diff_key($query, ['SS' => true]) : $query;
}

/**
 * The return URL controller. $order is the order the customer is paying (loaded from the session
 * or by the returned VS), with the amount and the symbols you sent in purchase().
 *
 * @param array{vs: string, ss: ?string, amount: string, status: string} $order
 */
function handleReturn(Request $request, array &$order): string
{
    // in a framework, pass the current request; plain PHP can use new Gateway() (createFromGlobals)
    $gateway = new Gateway(null, $request);
    $gateway->setSharedSecret(SHARED_SECRET);

    try {
        $response = $gateway->completePurchase(['vs' => $order['vs'], 'ss' => $order['ss']])->send();
    } catch (InvalidRequestException $e) {
        // forged, tampered or another order's return: never change the order, log it
        return '400 rejected: ' . $e->getMessage();
    }

    if (!$response->isSuccessful()) {
        // FAIL (rejected or cancelled) or TOUT (timed out): offer to pay again
        return "200 not paid ({$response->getRes()}), order stays {$order['status']}";
    }
    if ($order['status'] === 'paid') {
        // reload or back button: the same return again, mark the order paid only once
        return '200 already paid';
    }
    // RES=OK. The bank does not sign the amount: the amount is the one stored with the order.
    $order['status'] = 'paid';

    return "200 paid {$order['amount']} EUR, VS {$response->getVs()}";
}

$order = ['vs' => '2024000123', 'ss' => null, 'amount' => '10.00', 'status' => 'new'];

$scenarios = [
    'cancelled by the customer' => bankReturn('2024000123', 'FAIL'),
    'timed out' => bankReturn('2024000123', 'TOUT'),
    'RES changed to OK' => ['RES' => 'OK'] + bankReturn('2024000123', 'FAIL'),
    'return of another order' => bankReturn('2024000999', 'OK'),
    'return URL opened directly' => [],
    'paid' => bankReturn('2024000123', 'OK'),
    'paid, page reloaded' => bankReturn('2024000123', 'OK'),
];

foreach ($scenarios as $name => $query) {
    $request = Request::create('https://shop.example/return', 'GET', $query);
    printf("%-28s %s\n", $name . ':', handleReturn($request, $order));
}
