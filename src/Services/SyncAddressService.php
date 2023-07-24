<?php

namespace PavloDotDev\LaravelTronModule\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use PavloDotDev\LaravelTronModule\Api\DTO\TransferDTO;
use PavloDotDev\LaravelTronModule\Api\DTO\TRC20TransferDTO;
use PavloDotDev\LaravelTronModule\Enums\TronTransactionType;
use PavloDotDev\LaravelTronModule\Facades\Tron;
use PavloDotDev\LaravelTronModule\Handlers\WebhookHandlerInterface;
use PavloDotDev\LaravelTronModule\Models\TronAddress;
use PavloDotDev\LaravelTronModule\Models\TronTransaction;
use PavloDotDev\LaravelTronModule\Models\TronTRC20;

class SyncAddressService
{
    protected TronAddress $address;
    protected readonly array $trc20Addresses;

    public function __construct()
    {
        $this->trc20Addresses = TronTRC20::pluck('address')->all();
    }

    public function run(TronAddress $address): void
    {
        $this->address = $address;

        $this
            ->accountWithResources()
            ->trc20Balances()
            ->transactions();
    }

    protected function accountWithResources(): self
    {
        $getAccount = Tron::api()->getAccount($this->address->address);
        $getAccountResources = Tron::api()->getAccountResources($this->address->address);

        $this->address->update([
            'activated' => $getAccount->activated,
            'balance' => $getAccount->balance,
            'account' => $getAccount->toArray(),
            'account_resources' => $getAccountResources->toArray(),
        ]);

        return $this;
    }

    protected function trc20Balances(): self
    {
        $this->address->trc20 = TronTRC20::get()->mapWithKeys(function (TronTRC20 $trc20) {
            return [
                $trc20->address => $trc20->contract()->balanceOf($this->address->address)->toString(),
            ];
        })->all();
        $this->address->save();

        return $this;
    }

    protected function transactions(): self
    {
        $transfers = Tron::api()
            ->getTransfers($this->address->address)
            ->limit(200)
            ->searchInterval(false)
            ->minTimestamp(($this->address->sync_at?->getTimestamp() ?? 0) * 1000);

        $trc20Transfers = Tron::api()
            ->getTRC20Transfers($this->address->address)
            ->limit(200)
            ->minTimestamp(($this->address->sync_at?->getTimestamp() ?? 0) * 1000);

        $this->address->update([
            'sync_at' => Date::now(),
        ]);

        $transactions = [];

        foreach ($transfers as $transfer) {
            $transaction = $this->handleTransfer($transfer);
            if( $transaction ) {
                $transactions[] = $transaction;
            }
        }

        foreach ($trc20Transfers as $trc20Transfer) {
            $transaction = $this->handlerTRC20Transfer($trc20Transfer);
            if( $transaction ) {
                $transactions[] = $transaction;
            }
        }

        foreach($transactions as $transaction) {
            $this->webhook($transaction);
        }

        return $this;
    }

    protected function handleTransfer(TransferDTO $transfer): TronTransaction
    {
        return TronTransaction::updateOrCreate([
            'txid' => $transfer->txid,
            'address' => $this->address->address,
        ], [
            'type' => $transfer->to === $this->address->address ? TronTransactionType::INCOMING : TronTransactionType::OUTGOING,
            'time_at' => $transfer->time,
            'from' => $transfer->from,
            'to' => $transfer->to,
            'amount' => $transfer->value,
            'debug_data' => $transfer->toArray(),
        ]);
    }

    protected function handlerTRC20Transfer(TRC20TransferDTO $transfer): ?TronTransaction
    {
        if (!in_array($transfer->contractAddress, $this->trc20Addresses)) {
            return null;
        }

        return TronTransaction::updateOrCreate([
            'txid' => $transfer->txid,
            'address' => $this->address->address,
        ], [
            'type' => $transfer->to === $this->address->address ? TronTransactionType::INCOMING : TronTransactionType::OUTGOING,
            'time_at' => $transfer->time,
            'from' => $transfer->from,
            'to' => $transfer->to,
            'amount' => $transfer->value,
            'trc20_contract_address' => $transfer->contractAddress,
            'debug_data' => $transfer->toArray(),
        ]);
    }

    protected function webhook(TronTransaction $transaction): void
    {
        if ($transaction->wasRecentlyCreated) {
            /** @var class-string<WebhookHandlerInterface> $webhookHandlerModel */
            $webhookHandlerModel = config('tron.webhook_handler');
            if ($webhookHandlerModel) {
                /** @var WebhookHandlerInterface $webhookHandler */
                $webhookHandler = App::make($webhookHandlerModel);
                App::call([$webhookHandler, 'handle'], [
                    'address' => $this->address,
                    'transaction' => $transaction
                ]);
            }
        }
    }
}
