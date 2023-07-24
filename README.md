![Pest Laravel Expectations](https://banners.beyondco.de/Tron.png?theme=light&packageManager=composer+require&packageName=pavlo-dot-dev%2Flaravel-tron-module&pattern=architect&style=style_1&description=Working+with+cryptocurrency+Tron%2C+supported+TRC-20+tokens&md=1&showWatermark=1&fontSize=100px&images=https%3A%2F%2Flaravel.com%2Fimg%2Flogomark.min.svg)

<a href="https://packagist.org/packages/pavlo-dot-dev/laravel-tron-module" target="_blank">
    <img style="display: inline-block; margin-top: 0.5em; margin-bottom: 0.5em" src="https://img.shields.io/packagist/v/pavlo-dot-dev/laravel-tron-module.svg?style=flat&cacheSeconds=3600" alt="Latest Version on Packagist">
</a>

<a href="https://github.com/pavlo-dot-dev/laravel-tron-module/actions?query=workflow%3Alint+branch%3Amain" target="_blank">
    <img style="display: inline-block; margin-top: 0.5em; margin-bottom: 0.5em" src="https://img.shields.io/github/actions/workflow/status/defstudio/telegraph/php-cs-fixer.yml?branch=main&label=code%20style&cacheSeconds=3600" alt="Code Style">
</a>

<a href="https://packagist.org/packages/pavlo-dot-dev/laravel-tron-module" target="_blank">
    <img style="display: inline-block; margin-top: 0.5em; margin-bottom: 0.5em" src="https://img.shields.io/packagist/dt/pavlo-dot-dev/laravel-tron-module.svg?style=flat&cacheSeconds=3600" alt="Total Downloads">
</a>

<a href="https://packagist.org/packages/pavlo-dot-dev/laravel-tron-module" target="_blank">
    <img style="display: inline-block; margin-top: 0.5em; margin-bottom: 0.5em" src="https://img.shields.io/packagist/l/pavlo-dot-dev/laravel-tron-module?style=flat&cacheSeconds=3600" alt="License">
</a>

<a href="https://pavlo.dev"><img alt="Website" src="https://img.shields.io/badge/Website-https://pavlo.dev-black"></a>
<a href="https://t.me/pavlo_dev"><img alt="Telegram" src="https://img.shields.io/badge/Telegram-@pavlo_dev-blue"></a>

---

**Laravel Tron Module** is a Laravel package for work with cryptocurrency Tron, with the support TRC-20 tokens.It allows you to generate HD wallets using mnemonic phrase, validate addresses, get addresses balances and resources, preview and send TRX/TRC-20 tokens. You can automate the acceptance and withdrawal of cryptocurrency in your application.

```php
$wallet = Tron::createWallet('wallet-name', 'password', 'mnemonic-phrase', 'mnemonic-passphrase');
$address = Tron::createAddress($wallet, 0);

// Get Address Balance
$balance = Tron::api()->getAccount($address->address)->balance;

// Transfer TRX
Tron::api()->transfer($address->address, 'address-receiver', '1');
```

## Install

```bash
> composer require pavlo-dot-dev/laravel-tron-module
> php artisan vendor:publish --tag=tron-config
> php artisan migrate
```

In file `app/Console/Kernel` in method `schedule(Schedule $schedule)` add 
```
$schedule->command('tron:scan')->everyMinute();
```

In .env file add:
```
TRONGRID_API_KEY="..."
```

## Commands

Scan transactions and update balances:

```bash
> php artisan tron:scan
```

Create TRC-20 Token:

```bash
> php artisan tron:new-trc20
```

Create Wallet:

```bash
> php artisan tron:new-wallet
```

Generate Address:

```bash
> php artisan tron:generate-address
```

## Requirements

The following versions of PHP are supported by this version.

* PHP 8.0 and older
* PHP Extensions: Decimal, GMP, BCMath.
* Laravel Queues