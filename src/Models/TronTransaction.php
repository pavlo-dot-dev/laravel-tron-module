<?php

namespace PavloDotDev\LaravelTronModule\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use PavloDotDev\LaravelTronModule\Casts\DecimalCast;
use PavloDotDev\LaravelTronModule\Enums\TronTransactionType;
use PavloDotDev\LaravelTronModule\Facades\Tron;

class TronTransaction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'txid',
        'address',
        'type',
        'time_at',
        'from',
        'to',
        'amount',
        'trc20_contract_address',
        'get_transaction',
        'trc20_transaction_data',
    ];

    protected $appends = [
        'symbol'
    ];

    protected $casts = [
        'type' => TronTransactionType::class,
        'time_at' => 'datetime',
        'amount' => DecimalCast::class,
        'get_transaction' => 'json',
        'trc20_transaction_data' => 'json',
    ];

    public function addresses(): HasMany
    {
        /** @var class-string<TronAddress> $addressModel */
        $addressModel = config('tron.models.address');

        return $this->hasMany($addressModel, 'address', 'address');
    }

    public function wallets(): HasManyThrough
    {
        /** @var class-string<TronWallet> $walletModel */
        $walletModel = config('tron.models.wallet');

        /** @var class-string<TronAddress> $addressModel */
        $addressModel = config('tron.models.address');

        return $this->hasManyThrough(
            $walletModel,
            $addressModel,
            'address',
            'id',
            'address',
            'wallet_id'
        );
    }

    public function symbol(): Attribute
    {
        return new Attribute(
            get: fn() => $this->trc20_contract_address ? Tron::getTrc20($this->trc20_contract_address)?->symbol : 'TRX'
        );
    }
}
