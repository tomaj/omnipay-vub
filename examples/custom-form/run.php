<?php

declare(strict_types=1);

// Checkout page that renders its own "Pay with VÚB" button instead of Omnipay's auto-submitting page.
// The form is built from getRedirectUrl() and getRedirectData(); empty optional fields are left out.

use Omnipay\Eplatby\Gateway;

require __DIR__ . '/vendor/autoload.php';

$gateway = new Gateway();
$gateway->setMid('12345678');
$gateway->setSharedSecret(str_repeat('1', 64));
$gateway->setTestMode(true);
$gateway->setTestHost('http://127.0.0.1:8081');   // your own payment simulator instead of platby.tomaj.sk

$order = ['vs' => '2024000124', 'amount' => '24.90', 'phone' => '+421900123456'];

$response = $gateway->purchase([
    'amount' => $order['amount'],
    'vs' => $order['vs'],
    'rurl' => 'https://shop.example/return',
    'rsms' => $order['phone'],   // SMS notification from the bank (optional, not signed)
])->send();

$fields = array_filter($response->getRedirectData(), static fn(mixed $value): bool => is_string($value) && $value !== '');

$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
?>
<form method="<?= $escape(strtolower($response->getRedirectMethod())) ?>" action="<?= $escape($response->getRedirectUrl()) ?>" class="pay-vub">
<?php foreach ($fields as $name => $value) { ?>
    <input type="hidden" name="<?= $escape((string) $name) ?>" value="<?= $escape($value) ?>">
<?php } ?>
    <p>Order <?= $escape($order['vs']) ?>: <strong><?= $escape($order['amount']) ?> EUR</strong></p>
    <button type="submit">Pay with VÚB e-platby</button>
</form>
