<?php

namespace PavloDotDev\LaravelTronModule;

use IEXBase\TronAPI\Provider\HttpProvider;
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

        $this->app->bind(\IEXBase\TronAPI\Tron::class, function() {
            $fullNode = config('tron.full_node') ? new HttpProvider(config('tron.full_node'), 30000, false, false, [
                'TRON-PRO-API-KEY' => config('tron.trongrid_api_key'),
            ]) : null;
            $solidityNode = config('tron.solidity_node') ? new HttpProvider(config('tron.solidity_node'), 30000, false, false, [
                'TRON-PRO-API-KEY' => config('tron.trongrid_api_key'),
            ]) : null;
            $eventServer = config('tron.event_server') ? new HttpProvider(config('tron.event_server'), 30000, false, false, [
                'TRON-PRO-API-KEY' => config('tron.trongrid_api_key'),
            ]) : null;
            $signServer = config('tron.sign_server') ? new HttpProvider(config('tron.sign_server'), 30000, false, false, [
                'TRON-PRO-API-KEY' => config('tron.trongrid_api_key'),
            ]) : null;

            return new \IEXBase\TronAPI\Tron($fullNode, $solidityNode, $eventServer, $signServer);
        });

        $this->app->bind(TronGrid::class, function() {
            return new TronGrid(config('tron.trongrid_api_key'));
        });
    }
}
