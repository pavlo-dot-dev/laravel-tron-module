<?php

namespace PavloDotDev\LaravelTronModule\DTO;

use Decimal\Decimal;
use PavloDotDev\LaravelTronModule\Facades\Tron;

class AddressInfoDTO
{
    public function __construct(
        public readonly string   $address,
        public readonly bool     $activated,
        public readonly ?Decimal $balance,
        public readonly ?int     $bandwidthTotal,
        public readonly ?int     $bandwidthUsed,
        public readonly ?int     $bandwidthAvailable,
        public readonly ?int     $energyTotal,
        public readonly ?int     $energyUsed,
        public readonly ?int     $energyAvailable,
        public readonly array    $account,
        public readonly ?array   $accountResources,
    )
    {
    }

    public static function fromApi(string $address, \IEXBase\TronAPI\Tron $api): static
    {
        $account = $api->getAccount($address);
        $accountResources = !isset($account['create_time']) ? null : $api->getAccountResources($address);

        return static::fromArray($address, $account, $accountResources);
    }

    public static function fromArray(string $address, array $account, array $accountResources = null): static
    {
        $activated = isset($account['create_time']);

        return new static(
            address: $address,
            activated: $activated,
            balance: !$activated ? null : (new Decimal($account['balance'] ?? 0))->div(pow(10, 6)),
            bandwidthTotal: !$activated ? null : $accountResources['freeNetLimit'] ?? 0,
            bandwidthUsed: !$activated ? null : $accountResources['freeNetUsed'] ?? 0,
            bandwidthAvailable: !$activated ? null : ($accountResources['freeNetLimit'] ?? 0) - ($accountResources['freeNetUsed'] ?? 0),
            energyTotal: !$activated ? null : $accountResources['EnergyLimit'] ?? 0,
            energyUsed: !$activated ? null : $accountResources['EnergyUsed'] ?? 0,
            energyAvailable: !$activated ? null : ($accountResources['EnergyLimit'] ?? 0) - ($accountResources['EnergyUsed'] ?? 0),
            account: $account,
            accountResources: !$activated ? null : $accountResources,
        );
    }
}
