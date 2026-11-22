Omnipay VUB - ePlatby
=====================

[![CI](https://github.com/tomaj/omnipay-vub/actions/workflows/ci.yml/badge.svg)](https://github.com/tomaj/omnipay-vub/actions/workflows/ci.yml)

VUB ePlatby driver for the [Omnipay](https://omnipay.thephpleague.com/) payment processing library.

Requirements
------------

- PHP 8.3 or newer
- `omnipay/common` ^3.2 and a PSR-18 / HTTPlug client implementation (for example `php-http/guzzle7-adapter`)

Installation
------------

```bash
composer require tomaj/omnipay-vub
```

Development
-----------

```bash
composer check   # code style, PHPStan, lint
composer cs-fix  # apply code style
```
