<?php

namespace PavloDotDev\LaravelTronModule\Concerns;

use BIP\BIP44;
use PavloDotDev\LaravelTronModule\Exceptions\WalletLocked;
use PavloDotDev\LaravelTronModule\Models\TronAddress;
use PavloDotDev\LaravelTronModule\Models\TronWallet;
use Tron\Support\Key;
use Tron\Support\Key as SupportKey;

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

        if( $index === null ) {
            $index = $wallet->addresses()->max('index');
            $index = $index === null ? 0 : ($index + 1);
        }

        $hdKey = BIP44::fromMasterSeed($wallet->encrypted()->seed())
            ->derive("m/44'/195'/0'/0")
            ->deriveChild($index);
        $privateKey = (string)$hdKey->privateKey;

        $address = SupportKey::getBase58CheckAddress('41' . Key::privateKeyToAddress($privateKey));

        /** @var class-string<TronAddress> $addressModel */
        $addressModel = config('tron.models.address');

        return new $addressModel([
            'wallet_id' => $wallet->id,
            'address' => $address,
            'index' => $index,
            'private_key' => $wallet->encrypted()->encode($privateKey),
        ]);
    }
}
