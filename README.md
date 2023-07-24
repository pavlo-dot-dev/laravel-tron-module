## Introduction

Laravel Tron Module is a module for working with the TRON cryptocurrency, it allows you to generate addresses, track transactions, transfer money, receive balances, validate addresses.

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