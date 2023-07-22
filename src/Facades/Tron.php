<?php

namespace PavloDotDev\LaravelTronModule\Facades;

use Illuminate\Support\Facades\Facade;

class Tron extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \PavloDotDev\LaravelTronModule\Tron::class;
    }
}
