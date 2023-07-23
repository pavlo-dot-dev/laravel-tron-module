<?php

namespace PavloDotDev\LaravelTronModule\Handlers;

use Illuminate\Support\Facades\Log;
use PavloDotDev\LaravelTronModule\Models\TronAddress;
use PavloDotDev\LaravelTronModule\Models\TronTransaction;

class EmptyWebhookHandlerInterface implements WebhookHandlerInterface
{
    public function handle(TronAddress $address, TronTransaction $transaction): void
    {
        Log::error('NEW TRANSACTION FOR ADDRESS '.$address->id.' = '.$transaction->txid);
    }
}
