<?php

namespace PavloDotDev\LaravelTronModule\Actions\Trc20;

use Decimal\Decimal;
use PavloDotDev\LaravelTronModule\Facades\Tron;

class Preview
{
    public ?string $error = null;
    public ?Decimal $balanceBefore = null, $balanceAfter = null;
    public ?Decimal $tokenBefore = null, $tokenAfter = null;
    public ?bool $activated = null;

    public function __construct(
        public readonly string  $contractAddress,
        public readonly string  $from,
        public readonly string  $to,
        public readonly Decimal $amount,
        protected readonly \IEXBase\TronAPI\Tron $api
    )
    {
        try {
            $this->init();
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }
    }

    protected function init(): void
    {
        $fromInfo = Tron::getAddressInfo($this->from);
        if (!$fromInfo->activated) {
            throw new \Exception('From address not activated!');
        }
        $this->balanceBefore = $fromInfo->balance;
        $this->balanceAfter = $this->balanceBefore->copy();

        $fromTrc20Info = Tron::getAddressTrc20Info($this->from);
        $this->tokenBefore = $fromTrc20Info->get($this->contractAddress);
        $this->tokenAfter = $this->tokenBefore->sub($this->amount);
        if( $this->tokenAfter < 0 ) {
            throw new \Exception('Insufficient token balance');
        }

        $toInfo = Tron::getAddressInfo($this->to);
        $this->activated = $toInfo->activated;

        $contract = $this->api->contract($this->contractAddress);
        $contractDecimals = $contract->decimals();

        $test = $this->api->getTransactionBuilder()->triggerConstantContract(
            abi: Tron::getTrc20AbiData(),
            contract: $this->api->address2HexString($this->contractAddress),
            function: 'transfer',
            params: [
                $this->api->address2HexString($this->to),
                (string)$this->amount->mul(pow(10, $contractDecimals))->toInt(),
            ],
            address: $this->api->address2HexString($this->from),
        );


        print_r($test);

    }

    public function isOK(): bool
    {
        return !!$this->error;
    }

    public function toArray(): array
    {
        return [
            'error' => $this->error,
            'balance' => [
                'before' => $this->balanceBefore?->toString(),
                'after' => $this->balanceAfter?->toString(),
            ],
            'token' => [
                'before' => $this->tokenBefore?->toString(),
                'after' => $this->tokenAfter?->toString(),
            ],
            'activated' => $this->activated,
        ];
    }
}
