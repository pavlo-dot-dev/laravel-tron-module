<?php

namespace PavloDotDev\LaravelTronModule\Services;

use Decimal\Decimal;
use IEXBase\TronAPI\Tron;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use PavloDotDev\LaravelTronModule\Api\Helpers\AmountHelper;
use PavloDotDev\LaravelTronModule\DTO\AddressInfoDTO;
use PavloDotDev\LaravelTronModule\Enums\TronTransactionType;
use PavloDotDev\LaravelTronModule\Models\TronAddress;
use PavloDotDev\LaravelTronModule\Models\TronTransaction;
use PavloDotDev\LaravelTronModule\Models\TronTRC20;
use PavloDotDev\LaravelTronModule\TronGrid;

class SyncAddressService
{
    protected TronAddress $address;

    public function __construct(
        protected readonly Tron     $api,
        protected readonly TronGrid $tronGrid
    )
    {
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
        /** @var AddressInfoDTO $addressInfo */
        $addressInfo = \PavloDotDev\LaravelTronModule\Facades\Tron::getAddressInfo($this->address->address);

        $this->address->update([
            'activated' => $addressInfo->activated,
            'balance' => $addressInfo->balance,
            'account' => $addressInfo->account,
            'account_resources' => $addressInfo->accountResources,
        ]);

        return $this;
    }

    protected function trc20Balances(): self
    {
        $this->address->trc20 = \PavloDotDev\LaravelTronModule\Facades\Tron::getTrc20()
            ->mapWithKeys(function (TronTRC20 $trc20) {
                return [
                    $trc20->address => $this->api
                        ->contract($trc20->address)
                        ->balanceOf($this->address->address)
                ];
            })
            ->all();
        $this->address->save();

        return $this;
    }

    protected function transactions(): self
    {
        $getTransactions = $this->getTransactions();
        $getTrc20Transactions = $this->getTrc20Transactions();

        $this->address->update([
            'sync_at' => Date::now(),
        ]);

        foreach ($getTransactions as $item) {
            switch ($item['raw_data']['contract'][0]['type'] ?? null) {
                case 'TransferContract':
                    $this->handleTransferContract($item);
                    break;
            }
        }

        foreach ($getTrc20Transactions as $tokenData) {
            $transactionData = $getTransactions->first(fn(array $item) => $item['txID'] === $tokenData['transaction_id']);
            $this->handleTriggerSmartContract($tokenData, $transactionData);
        }

        return $this;
    }

    protected function handleTransferContract(array $transactionData): TronTransaction
    {
        $fromAddress = $this->api->hexString2Address($transactionData['raw_data']['contract'][0]['parameter']['value']['owner_address']);
        $toAddress = $this->api->hexString2Address($transactionData['raw_data']['contract'][0]['parameter']['value']['to_address']);
        $amount = $transactionData['raw_data']['contract'][0]['parameter']['value']['amount'];

        return TronTransaction::updateOrCreate([
            'txid' => $transactionData['txID'],
            'address' => $this->address->address,
        ], [
            'type' => $toAddress === $this->address->address ? TronTransactionType::INCOMING : TronTransactionType::OUTGOING,
            'time_at' => Date::createFromTimestampMs($transactionData['block_timestamp']),
            'from' => $fromAddress,
            'to' => $toAddress,
            'amount' => AmountHelper::sunToDecimal($amount),
            'get_transaction' => $transactionData,
        ]);
    }

    protected function handleTriggerSmartContract(array $tokenData, array $transactionData = null): ?TronTransaction
    {
        if ($tokenData['type'] !== 'Transfer') {
            return null;
        }

        if ($transactionData === null) {
            $transactionData = $this->api->getTransaction($tokenData['transaction_id']);
        }

        if (($transactionData['ret'][0]['contractRet'] ?? null) !== 'SUCCESS') {
            return null;
        }

        $fromAddress = $tokenData['from'];
        $toAddress = $tokenData['to'];
        $contractAddress = $tokenData['token_info']['address'];
        $amount = AmountHelper::toDecimal($tokenData['value'], $tokenData['token_info']['decimals']);

        return TronTransaction::updateOrCreate([
            'txid' => $tokenData['transaction_id'],
            'address' => $this->address->address,
        ], [
            'type' => $toAddress === $this->address->address ? TronTransactionType::INCOMING : TronTransactionType::OUTGOING,
            'time_at' => Date::createFromTimestampMs($tokenData['block_timestamp']),
            'from' => $fromAddress,
            'to' => $toAddress,
            'amount' => $amount,
            'trc20_contract_address' => $contractAddress,
            'get_transaction' => $transactionData,
            'trc20_transaction_data' => $tokenData,
        ]);
    }

    protected function getTransactions(): Collection
    {
        $response = $this->tronGrid
            ->getTransactionsByAddress($this->address->address, [
                'search_internal' => 'false',
                'limit' => 200,
                'min_timestamp' => ($this->address->sync_at?->getTimestamp() ?? 0) * 1000,
            ]);

        return collect($response['data'] ?? [])
            ->filter(fn(array $item) => ($item['ret'][0]['contractRet'] ?? null) === 'SUCCESS');
    }

    protected function getTrc20Transactions(): Collection
    {
        $result = collect();

        foreach (\PavloDotDev\LaravelTronModule\Facades\Tron::getTrc20() as $trc20) {
            $response = $this->tronGrid
                ->getContractTransactionsByAddress($this->address->address, [
                    'limit' => 200,
                    'contract_address' => $trc20->address,
                    'min_timestamp' => ($this->address->sync_at?->getTimestamp() ?? 0) * 1000,
                ]);
            $result = $result->merge($response['data'] ?? []);
        }

        return $result;
    }
}
