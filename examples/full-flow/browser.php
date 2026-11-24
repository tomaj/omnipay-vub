<?php

declare(strict_types=1);

// Plays the customer's browser: php browser.php <shop url> <order> <choice> [tamper]
// Opens the checkout, submits the form to the bank like the browser would, follows the bank's
// redirect back to the shop and prints what each side answered (without host names and ports).
// With a path instead of an order (php browser.php <shop url> /orders) it only opens that page.

$shop = $argv[1] ?? 'http://127.0.0.1:8000';
$order = $argv[2] ?? '2024000123';
$choice = $argv[3] ?? 'OK';
$tamper = ($argv[4] ?? '') === 'tamper';

/**
 * @param array<string, string> $form
 * @return array{int, string, ?string} status, body, Location header
 */
function http(string $method, string $url, array $form = []): array
{
    $context = stream_context_create(['http' => [
        'method' => $method,
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($form),
        'follow_location' => 0,
        'ignore_errors' => true,
    ]]);
    $body = (string) file_get_contents($url, false, $context);
    $headers = http_get_last_response_headers() ?? [];
    $status = (int) explode(' ', $headers[0] ?? 'HTTP/1.1 0')[1];
    $location = null;
    foreach ($headers as $header) {
        if (stripos($header, 'Location:') === 0) {
            $location = trim(substr($header, 9));
        }
    }

    return [$status, $body, $location];
}

$hide = static fn(string $url): string => (string) preg_replace('~^https?://[^/]+~', '', $url);

if (str_starts_with($order, '/')) {
    [$status, $body] = http('GET', $shop . $order);
    echo "shop  {$status} " . $body;
    return;
}

[$status, $html] = http('GET', "{$shop}/checkout?order={$order}");
preg_match('~<form action="([^"]+)" method="post">~', $html, $action);
preg_match_all('~name="([^"]+)" value="([^"]*)"~', $html, $inputs, PREG_SET_ORDER);
$form = [];
foreach ($inputs as [, $name, $value]) {
    $form[html_entity_decode($name)] = html_entity_decode($value);
}
echo "shop  {$status} checkout form to " . $hide(html_entity_decode($action[1] ?? '')) . ', VS=' . $form['VS'] . ', AMT=' . $form['AMT'] . "\n";

if ($tamper) {
    $form['AMT'] = '0.01';
    echo "      the customer edits AMT to 0.01 before the form is posted\n";
}

[$status, $body, $location] = http('POST', html_entity_decode($action[1] ?? '') . '?choice=' . $choice, $form);
if ($location === null) {
    echo "bank  {$status} " . $body;
    return;
}
echo "bank  {$status} redirect to " . $hide($location) . "\n";

[$status, $body] = http('GET', $location);
echo "shop  {$status} " . $body;
