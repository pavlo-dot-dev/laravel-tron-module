<?php

namespace PavloDotDev\LaravelTronModule\Actions\Transfer;

use Decimal\Decimal;
use PavloDotDev\LaravelTronModule\Facades\Tron;

class Preview
{
    public ?string $error = null;
    public ?Decimal
        $balanceBefore = null,
        $balanceAfter = null;
    public ?bool $activateRequired = null;
    public ?Decimal $activateFee = null;
    public ?array $transaction = null;
    public ?int $bandwidthRequired = null,
        $bandwidthBefore = null,
        $bandwidthAfter = null;
    public ?Decimal $bandwidthFee = null;

    public function __construct(
        public readonly string  $from,
        public readonly string  $to,
        public readonly Decimal $amount,
    )
    {
        $this->init();
    }

    protected function init(): void
    {
        try {
            $fromInfo = Tron::getAddressInfo($this->from);
            if (!$fromInfo->activated) {
                $this->error = 'From address not activated!';
                return;
            }
            $this->balanceBefore = $fromInfo->balance;
            $this->balanceAfter = $fromInfo->balance->sub($this->amount);

            $toInfo = Tron::getAddressInfo($this->to);
            $this->activateRequired = !$toInfo->activated;
            $this->activateFee = (new Decimal(100000))->div(pow(10, 6));
            if ($this->activateRequired) {
                $this->balanceAfter = $this->balanceAfter->sub($this->activateFee);
            }

            try {
                $this->transaction = Tron::createTransfer($this->from, $this->to, $this->amount);
            }
            catch(\Exception $e) {
                if( mb_strpos($e->getMessage(), 'balance is not sufficient') !== false ) {
                    throw new \Exception('Insufficient balance');
                }

                throw $e;
            }

            $this->bandwidthRequired = $toInfo->activated ? strlen($this->transaction['raw_data_hex']) + 1 : 0;
            $this->bandwidthBefore = $fromInfo->bandwidthAvailable;
            if( $this->bandwidthRequired > $this->bandwidthBefore ) {
                $this->bandwidthFee = (new Decimal(($this->bandwidthRequired + 1) * 1000))->div(pow(10, 6));
                $this->balanceAfter = $this->balanceAfter->sub($this->bandwidthFee);
            }
            else {
                $this->bandwidthFee = new Decimal(0);
                $this->bandwidthAfter = $this->bandwidthBefore - $this->bandwidthRequired;
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return;
        }
    }

    public function isOK(): bool
    {
        return $this->error === null;
    }

    public function toArray(): array
    {
        return [
            'error' => $this->error,
            'balance' => [
                'before' => $this->balanceBefore?->toString(),
                'after' => $this->balanceAfter?->toString(),
            ],
            'activate' => [
                'required' => $this->activateRequired,
                'fee' => $this->activateFee?->toString(),
            ],
            'bandwidth' => [
                'required' => $this->bandwidthRequired,
                'before' => $this->bandwidthBefore,
                'after' => $this->bandwidthAfter,
            ]
        ];
    }
}
