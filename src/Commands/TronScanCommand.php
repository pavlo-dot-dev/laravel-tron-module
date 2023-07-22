<?php

namespace PavloDotDev\LaravelTronModule\Commands;

use Decimal\Decimal;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;
use PavloDotDev\LaravelTronModule\Jobs\SyncAddressJob;
use PavloDotDev\LaravelTronModule\Models\TronAddress;
use PavloDotDev\LaravelTronModule\Models\TronWallet;

class TronScanCommand extends Command
{
    protected $signature = 'tron:scan';

    protected $description = 'Start wallets synchronization';

    public function handle(): void
    {
        TronWallet::query()
            ->whereActive(true)
            ->each(fn(TronWallet $wallet) => $this->eachWallet($wallet));
    }

    protected function eachWallet(TronWallet $wallet): void
    {
        $trackedAddresses = $wallet->addresses;
        if( count( $trackedAddresses ) === 0 ) {
            return;
        }

        $jobs = $trackedAddresses->map(fn(TronAddress $address) => new SyncAddressJob($address));

        Bus::batch($jobs)
            ->withOption('wallet', $wallet->id)
            ->finally(function (Batch $batch) {
                $wallet = TronWallet::find($batch->options['wallet']);
                $balance = new Decimal(0);
                $trc20 = [];

                foreach ($wallet->addresses as $address) {
                    $balance = $balance->add((string)($address->balance ?: 0));
                    foreach( $address->trc20 as $k => $v ) {
                        $current = new Decimal($trc20[$k] ?? 0);
                        $trc20[$k] = $current->add($v)->toString();
                    }
                }

                $wallet->update([
                    'sync_at' => Date::now(),
                    'balance' => $balance,
                    'trc20' => $trc20,
                ]);
            })
            ->dispatch();
    }
}
