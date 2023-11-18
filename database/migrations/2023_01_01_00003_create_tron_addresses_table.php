<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PavloDotDev\LaravelTronModule\Models\TronWallet;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tron_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TronWallet::class, 'wallet_id')
                ->constrained('tron_wallets')
                ->cascadeOnDelete();
            $table->string('address');
            $table->string('title')->nullable();
            $table->boolean('watch_only')->nullable();
            $table->text('private_key')->nullable();
            $table->unsignedInteger('index')->nullable();
            $table->timestamp('sync_at')->nullable();
            $table->boolean('activated')->nullable();
            $table->decimal('balance', 20, 6)->nullable();
            $table->json('trc20')->default('[]');
            $table->json('account')->nullable();
            $table->json('account_resources')->nullable();
            $table->timestamps();

            $table->unique(['wallet_id', 'index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tron_addresses');
    }
};
