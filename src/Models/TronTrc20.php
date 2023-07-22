<?php

namespace PavloDotDev\LaravelTronModule\Models;

use Illuminate\Database\Eloquent\Model;

class TronTrc20 extends Model
{
    public $timestamps = false;

    protected $table = 'tron_trc20';

    protected $fillable = [
        'address',
        'name',
        'symbol',
        'decimals',
    ];

    protected $casts = [
        'decimals' => 'integer',
    ];
}
