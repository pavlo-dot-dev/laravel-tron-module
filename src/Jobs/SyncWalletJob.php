<?php

namespace PavloDotDev\LaravelTronModule\Jobs;

use Decimal\Decimal;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use PavloDotDev\LaravelTronModule\Models\TronWallet;

class SyncWalletJob implements ShouldQueue, ShouldBeUnique
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(protected readonly TronWallet $wallet)
    {
    }

    public function uniqueId(): string
    {
        return $this->wallet->id;
    }

    public function handle(): void
    {
        foreach( $this->wallet->addresses as $address ) {
            SyncAddressJob::dispatchSync($address);
        }

        $balance = new Decimal(0);
        $trc20 = [];

        foreach ($this->wallet->addresses as $address) {
            $balance = $balance->add((string)($address->balance ?: 0));
            foreach( $address->trc20 as $k => $v ) {
                $current = new Decimal($trc20[$k] ?? 0);
                $trc20[$k] = $current->add($v)->toString();
            }
        }

        $this->wallet->update([
            'sync_at' => Date::now(),
            'balance' => $balance,
            'trc20' => $trc20,
        ]);
    }
}
