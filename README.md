## Introduction

Laravel Tron Module is a module for working with the TRON cryptocurrency, it allows you to generate addresses, track transactions, transfer money, receive balances, validate addresses.

## Install

```bash
> composer require pavlo-dot-dev/laravel-tron-module --ignore-platform-reqs
> php artisan vendor:publish --tag=tron-config
```

In file `app/Console/Kernel` in method `schedule(Schedule $schedule)` add 
```
$schedule->command('tron:scan')->everyMinute();
```

In .env file add:
```
TRONGRID_API_KEY="..."
```

## Requirements

The following versions of PHP are supported by this version.

* PHP 8.0 and older
* PHP Extensions: Decimal, GMP, BCMath.