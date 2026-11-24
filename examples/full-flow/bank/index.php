<?php

declare(strict_types=1);

// A fake VÚB e-platby endpoint for this example: php -S 127.0.0.1:8001 bank/index.php
// It does what the bank (or tomaj/payment-simulator) does, without the library:
//   POST /payment/eplatby-hmac  checks SIGN = HMAC-SHA256(MID + AMT + VS + SS + CS + RURL) and
//   redirects to RURL with VS, SS, RES and SIGN = HMAC-SHA256(VS + SS + RES).
// The customer's choice comes from ?choice=OK|FAIL|TOUT (the buttons of the real payment page).

$secret = getenv('VUB_SHARED_SECRET');
$secret = is_string($secret) && $secret !== '' ? $secret : str_repeat('1', 64);
$uri = $_SERVER['REQUEST_URI'] ?? '';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !is_string($uri) || parse_url($uri, PHP_URL_PATH) !== '/payment/eplatby-hmac') {
    http_response_code(404);
    echo "Not found\n";
    return;
}

$field = static fn(string $name): string => is_string($_POST[$name] ?? null) ? $_POST[$name] : '';

$expected = strtoupper(hash_hmac('sha256', $field('MID') . $field('AMT') . $field('VS') . $field('SS') . $field('CS') . $field('RURL'), $secret));
if (!hash_equals($expected, $field('SIGN'))) {
    http_response_code(400);
    echo "e-platby: the payment request has an invalid signature\n";
    return;
}

$res = $_GET['choice'] ?? 'OK';
$res = in_array($res, ['OK', 'FAIL', 'TOUT'], true) ? $res : 'FAIL';
$query = ['VS' => $field('VS')];
if ($field('SS') !== '') {
    $query['SS'] = $field('SS');
}
$query['RES'] = $res;
$query['SIGN'] = strtoupper(hash_hmac('sha256', $field('VS') . $field('SS') . $res, $secret));

header('Location: ' . $field('RURL') . '?' . http_build_query($query), true, 302);
