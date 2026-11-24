<p align="center"><a href="https://tomaj.github.io/omnipay-vub/"><img src="docs/public/logo.svg" width="96" height="96" alt="omnipay-vub"></a></p>

# Omnipay VÚB e-platby

**VÚB e-platby (VÚB banka, Slovakia) gateway for the [Omnipay](https://omnipay.thephpleague.com/) payment processing library**

**Documentation: https://tomaj.github.io/omnipay-vub/**

[Getting started](https://tomaj.github.io/omnipay-vub/guide/getting-started) · [Payment flow](https://tomaj.github.io/omnipay-vub/guide/payment-flow) · [Examples](https://tomaj.github.io/omnipay-vub/examples/) · [API reference](https://tomaj.github.io/omnipay-vub/api/reference) · [Changelog](https://tomaj.github.io/omnipay-vub/changelog)

[![Latest Stable Version](https://img.shields.io/packagist/v/tomaj/omnipay-vub.svg)](https://packagist.org/packages/tomaj/omnipay-vub)
[![CI](https://github.com/tomaj/omnipay-vub/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/tomaj/omnipay-vub/actions/workflows/ci.yml?query=branch%3Amaster)
[![Coverage Status](https://coveralls.io/repos/github/tomaj/omnipay-vub/badge.svg?branch=master)](https://coveralls.io/github/tomaj/omnipay-vub?branch=master)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen.svg)](https://phpstan.org/)
[![OpenSSF Scorecard](https://api.securityscorecards.dev/projects/github.com/tomaj/omnipay-vub/badge)](https://scorecard.dev/viewer/?uri=github.com/tomaj/omnipay-vub)
[![Documentation](https://img.shields.io/badge/docs-4.x-4f46e5.svg)](https://tomaj.github.io/omnipay-vub/)
[![License](https://img.shields.io/packagist/l/tomaj/omnipay-vub.svg)](LICENSE.md)
[![PHP Version](https://img.shields.io/packagist/dependency-v/tomaj/omnipay-vub/php.svg)](https://packagist.org/packages/tomaj/omnipay-vub)

## What is omnipay-vub?

An Omnipay gateway (`Eplatby`) for [VÚB e-platby](https://www.vub.sk/en/firmy-a-podnikatelia/platby/eplatby.html), the internet banking payment button of VÚB banka. It:

- builds the signed (HMAC-SHA256) payment form that redirects the customer to VÚB internet banking (`purchase()`),
- verifies the signed return request of the bank and checks it belongs to the order you expect (`completePurchase()`),
- can send payments to a [payment simulator](https://github.com/tomaj/payment-simulator) in test mode.

## Installation

The library requires PHP 8.4 or later and an HTTP client for Omnipay (any PSR-18 client, for example `php-http/guzzle7-adapter`).

```bash
composer require tomaj/omnipay-vub php-http/guzzle7-adapter
```

## Usage

Redirect the customer to the bank:

```php
use Omnipay\Omnipay;

$gateway = Omnipay::create('Eplatby');
$gateway->setMid('your-mid');
$gateway->setSharedSecret('your-shared-secret');

$response = $gateway->purchase([
    'amount' => '10.00',
    'vs' => '2024000123',   // variable symbol, unique per order
    'rurl' => 'https://shop.example/payment/return',
])->send();

$response->redirect(); // auto-submitting POST form to VÚB e-platby
```

On the return URL, verify the bank's answer against the order you expect:

```php
$response = $gateway->completePurchase(['vs' => $order->vs])->send();

if ($response->isSuccessful()) {
    // paid: the amount is not part of the bank's signature, use the amount stored with the order
}
```

The [documentation](https://tomaj.github.io/omnipay-vub/) covers every parameter, the signed strings, test mode, error handling and testing. The [examples](https://tomaj.github.io/omnipay-vub/examples/) in [`examples/`](examples) run in CI, from the payment form to a shop and a fake bank on localhost.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). This project follows the [Contributor Covenant Code of Conduct](CODE_OF_CONDUCT.md).

## Security

Report vulnerabilities privately, see [SECURITY.md](SECURITY.md).

## License

MIT, see [LICENSE.md](LICENSE.md).
