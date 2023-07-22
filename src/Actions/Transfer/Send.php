<?php

namespace PavloDotDev\LaravelTronModule\Actions\Transfer;

use IEXBase\TronAPI\Tron;
use PavloDotDev\LaravelTronModule\Models\TronAddress;
use PavloDotDev\LaravelTronModule\Models\TronWallet;

class Send
{
    public readonly array $signedTransaction;

    public function __construct(
        public readonly TronWallet     $wallet,
        protected readonly TronAddress $from,
        public readonly Preview        $preview,
        protected readonly Tron        $api,

    )
    {
        $this->init();
    }

    protected function init(): void
    {
        if (!$this->wallet->encrypted()->isUnlocked()) {
            throw new \Exception('Wallet is not unlocked');
        }
        if (!$this->preview->isOK()) {
            throw new \Exception($this->preview->error);
        }

        $privateKey = $this->wallet->encrypted()->decode($this->from->private_key);

        $this->api->setPrivateKey($privateKey);
        $this->signedTransaction = $this->api->signTransaction($this->preview->transaction);
        if (isset($this->signedTransaction['Error'])) {
            throw new \Exception($this->signedTransaction['Error']);
        }
    }

    public function send(): string
    {
        $response = $this->api->sendRawTransaction($this->signedTransaction);
        if (!isset($response['txid'])) {
            throw new \Exception($response['Error'] ?? print_r($response, true));
        }

        return $response['txid'];
    }
}
