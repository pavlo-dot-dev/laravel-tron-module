<?php

namespace PavloDotDev\LaravelTronModule;

use PavloDotDev\LaravelTronModule\Api\Api;
use PavloDotDev\LaravelTronModule\Concerns\Address;
use PavloDotDev\LaravelTronModule\Concerns\Mnemonic;
use PavloDotDev\LaravelTronModule\Concerns\Wallet;

class Tron
{
    use Mnemonic, Wallet, Address;

    public function __construct(
        protected readonly Api $api
    )
    {
    }

    /*
     * API Object
     */
    public function api(): Api
    {
        return $this->api;
    }
}
