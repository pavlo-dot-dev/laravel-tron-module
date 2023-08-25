<?php

namespace PavloDotDev\LaravelTronModule\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use PavloDotDev\LaravelTronModule\Models\TronAddress;
use PavloDotDev\LaravelTronModule\Services\SyncAddressService;

class SyncAddressJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(protected readonly TronAddress $address)
    {
    }

    public function handle(SyncAddressService $service): void
    {
        $service->run($this->address);
    }
}
