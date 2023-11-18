<?php

namespace PavloDotDev\LaravelTronModule\Commands;

use Illuminate\Console\Command;
use PavloDotDev\LaravelTronModule\Facades\Tron;
use PavloDotDev\LaravelTronModule\Models\TronWallet;

class CreateNewWalletCommand extends Command
{
    protected $signature = 'tron:new-wallet';

    protected $description = 'Create a new TronWallet';

    public function handle(): void
    {
        $this->info('You are about to create a new Tron Wallet');

        do {
            $error = false;
            $name = $this->ask('Please, enter unique wallet name');
            if (empty($name)) {
                $error = true;
                $this->error('Wallet name is required!');
            } else {
                if (TronWallet::whereName($name)->count() > 0) {
                    $error = true;
                    $this->error('Name is busy!');
                }
            }
        } while ($error);

        do {
            $error = false;
            $password = $this->ask('Please, enter wallet password');
            if (empty($password)) {
                $error = true;
                $this->error('Password is required!');
            }
        } while ($error);

        do {
            $error = false;
            $mnemonic = $this->ask('Please, enter mnemonic phrase (optional)');
            if (!empty($mnemonic) && !Tron::mnemonicValidate($mnemonic)) {
                $error = true;
                $this->error('Mnemonic Phrase is not valid!');
            }
        } while ($error);

        if (empty($mnemonic)) {
            $mnemonic = implode(' ', Tron::mnemonicGenerate());
        }

        $wallet = Tron::createWallet($name, $password, $mnemonic);
        $wallet->save();

        $this->info('Tron Wallet #'.$wallet->id.' successfully created!');
        $this->info('Mnemonic: '.$mnemonic);
    }
}
