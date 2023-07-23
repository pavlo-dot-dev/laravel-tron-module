<?php

namespace PavloDotDev\LaravelTronModule\Commands;

use Decimal\Decimal;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use PavloDotDev\LaravelTronModule\Jobs\SyncAddressJob;
use PavloDotDev\LaravelTronModule\Jobs\SyncWalletJob;
use PavloDotDev\LaravelTronModule\Models\TronAddress;
use PavloDotDev\LaravelTronModule\Models\TronWallet;

class TronScanCommand extends Command
{
    protected $signature = 'tron:scan';

    protected $description = 'Start wallets synchronization';

    public function handle(): void
    {
        /** @var class-string<TronWallet> $walletModel */
        $walletModel = config('tron.models.wallet');

        $walletModel::query()
            ->whereActive(true)
            ->each(fn(TronWallet $wallet) => $this->eachWallet($wallet));
    }

    protected function eachWallet(TronWallet $wallet): void
    {
        $trackedAddresses = $wallet->addresses;
        if( count( $trackedAddresses ) === 0 ) {
            return;
        }

        SyncWalletJob::dispatch($wallet);
    }
}
