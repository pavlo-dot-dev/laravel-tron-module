<?php

namespace PavloDotDev\LaravelTronModule;

use PavloDotDev\LaravelTronModule\Api\Api;
use PavloDotDev\LaravelTronModule\Api\HttpProvider;
use PavloDotDev\LaravelTronModule\Commands\TronScanCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TronServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('tron')
            ->hasConfigFile()
            ->hasMigrations([
                '2023_01_01_00001_create_tron_wallets_table',
                '2023_01_01_00002_create_tron_trc20_table',
                '2023_01_01_00003_create_tron_addresses_table',
                '2023_01_01_00004_create_tron_transactions_table'
            ])
            ->runsMigrations()
            ->hasCommands(TronScanCommand::class);

        $this->app->singleton(Api::class, function() {
            $fullNode = new HttpProvider(config('tron.full_node'), [
                'TRON-PRO-API-KEY' => config('tron.trongrid_api_key'),
            ]);
            $solidityNode = new HttpProvider(config('tron.solidity_node'), [
                'TRON-PRO-API-KEY' => config('tron.trongrid_api_key'),
            ]);
            return new Api($fullNode, $solidityNode);
        });

        $this->app->singleton(Tron::class);
    }
}
