<?php

namespace PavloDotDev\LaravelTronModule\Concerns;

use BIP\BIP44;
use PavloDotDev\LaravelTronModule\Api\Helpers\AddressHelper;
use PavloDotDev\LaravelTronModule\Exceptions\WalletLocked;
use PavloDotDev\LaravelTronModule\Facades\Tron;
use PavloDotDev\LaravelTronModule\Models\TronAddress;
use PavloDotDev\LaravelTronModule\Models\TronWallet;
use PavloDotDev\LaravelTronModule\Support\Key;

trait Address
{
    /*
     * Create Tron Address (without save in Database)
     */
    public function createAddress(TronWallet $wallet, int $index = null): TronAddress
    {
        if (!$wallet->encrypted()->isUnlocked()) {
            throw new WalletLocked();
        }

        if ($index === null) {
            $index = $wallet->addresses()->max('index');
            $index = $index === null ? 0 : ($index + 1);
        }

        $hdKey = BIP44::fromMasterSeed($wallet->encrypted()->seed())
            ->derive("m/44'/195'/0'/0")
            ->deriveChild($index);
        $privateKey = (string)$hdKey->privateKey;

        $address = AddressHelper::toBase58('41'.Key::privateKeyToAddress($privateKey));

        /** @var class-string<TronAddress> $addressModel */
        $addressModel = config('tron.models.address');

        return new $addressModel([
            'wallet_id' => $wallet->id,
            'address' => $address,
            'index' => $index,
            'private_key' => $wallet->encrypted()->encode($privateKey),
        ]);
    }

    public function importAddress(TronWallet $wallet, string $address)
    {
        /** @var class-string<TronAddress> $addressModel */
        $addressModel = config('tron.models.address');

        return new $addressModel([
            'wallet_id' => $wallet->id,
            'address' => $address,
            'watch_only' => true,
        ]);
    }
}
