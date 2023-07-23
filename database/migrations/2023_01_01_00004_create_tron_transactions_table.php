<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PavloDotDev\LaravelTronModule\Enums\TronTransactionType;
use PavloDotDev\LaravelTronModule\Models\TronTRC20;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tron_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('txid')->index();
            $table->string('address')->index();
            $table->enum('type', array_column(TronTransactionType::cases(), 'value'));
            $table->timestamp('time_at');
            $table->string('from');
            $table->string('to');
            $table->decimal('amount', 20, 6);
            $table->string('trc20_contract_address')->nullable();
            $table->json('get_transaction');
            $table->json('trc20_transaction_data')->nullable();

            $table->unique(['txid', 'address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tron_transactions');
    }
};
