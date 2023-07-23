<?php

namespace PavloDotDev\LaravelTronModule\Handlers;

use PavloDotDev\LaravelTronModule\Models\TronAddress;
use PavloDotDev\LaravelTronModule\Models\TronTransaction;

interface WebhookHandlerInterface
{
    public function handle(TronAddress $address, TronTransaction $transaction): void;
}
