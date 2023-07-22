<?php

namespace PavloDotDev\LaravelTronModule\DTO;

use Decimal\Decimal;
use IEXBase\TronAPI\Tron;
use Illuminate\Support\Collection;
use PavloDotDev\LaravelTronModule\Models\TronTrc20;

class AddressTrc20Info
{
    public function __construct(
        public readonly string     $address,
        public readonly Collection $balances
    )
    {
    }

    public function get(TronTrc20|string $contract): Decimal
    {
        $address = $contract instanceof TronTrc20 ? $contract->address : $contract;

        return $this->balances->get($address);
    }

    public function toArray(): array
    {
        return [
            'address' => $this->address,
            'balances' => $this->balances->map(fn(Decimal $item) => $item->toString())->all(),
        ];
    }

    public static function fromApi(string $address, Collection $trc20, Tron $api): static
    {
        $balances = collect();

        foreach( $trc20 as $item ) {
            $balance = $api->contract($item->address)->balanceOf($address);
            $balances->put($item->address, new Decimal($balance));
        }

        return new static(
            address: $address,
            balances: $balances
        );
    }
}
