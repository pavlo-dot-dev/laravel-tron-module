<?php

namespace PavloDotDev\LaravelTronModule\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PavloDotDev\LaravelTronModule\Casts\DecimalCast;
use PavloDotDev\LaravelTronModule\Facades\Tron;

class TronAddress extends Model
{
    protected $fillable = [
        'wallet_id',
        'address',
        'title',
        'private_key',
        'index',
        'sync_at',
        'activated',
        'balance',
        'trc20',
        'account',
        'account_resources',
    ];

    protected $appends = [
        'trc20_balances',
        'watch_only',
    ];

    protected $hidden = [
        'private_key',
        'trc20',
    ];

    protected $casts = [
        'sync_at' => 'datetime',
        'activated' => 'boolean',
        'balance' => DecimalCast::class,
        'trc20' => 'json',
        'account' => 'json',
        'account_resources' => 'json',
    ];

    public function wallet(): BelongsTo
    {
        /** @var class-string<TronWallet> $walletModel */
        $walletModel = config('tron.models.wallet');

        return $this->belongsTo($walletModel, 'wallet_id', 'id');
    }

    public function trc20Balances(): Attribute
    {
        return new Attribute(
            get: fn () => TronTRC20::get()->map(fn (TronTRC20 $trc20) => [
                ...$trc20->only(['address', 'name', 'symbol', 'decimals']),
                'balance' => $this->trc20[$trc20->address] ?? null,
            ])
        );
    }

    public function watchOnly(): Attribute
    {
        return new Attribute(
            get: fn () => is_null($this->private_key)
        );
    }
}
