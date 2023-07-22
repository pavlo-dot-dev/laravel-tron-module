<?php

namespace PavloDotDev\LaravelTronModule\Concerns;

use FurqanSiddiqui\BIP39\BIP39;
use PavloDotDev\LaravelTronModule\Models\TronWallet;

trait ManageWallet
{
    public function createWallet(string $name, string $password, string $title = null, int $mnemonicSize = 15): TronWallet
    {
        $mnemonic = BIP39::Generate($mnemonicSize);

        /** @var class-string<TronWallet> $walletModel */
        $walletModel = config('tron.models.wallet');

        $wallet = new $walletModel([
            'name' => $name,
            'title' => $title,
            'mnemonic' => implode(" ", $mnemonic->words),
            'seed' => bin2hex($mnemonic->generateSeed())
        ]);
        $wallet->encrypted()->encrypt($password);
        $wallet->save();

        return $wallet;
    }

    public function recoveryWallet(string $mnemonic, string $name, string $password, string $title = null): TronWallet
    {
        $mnemonic = BIP39::Words($mnemonic);

        /** @var class-string<TronWallet> $walletModel */
        $walletModel = config('tron.models.wallet');

        $wallet = new $walletModel([
            'name' => $name,
            'title' => $title,
            'mnemonic' => implode(" ", $mnemonic->words),
            'seed' => bin2hex($mnemonic->generateSeed())
        ]);
        $wallet->encrypted()->encrypt($password);
        $wallet->save();

        return $wallet;
    }
}