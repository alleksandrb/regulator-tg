<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account_post_views', function (Blueprint $table) {
            $table->comment('Таблица для хранения фактов просмотра постов аккаунтами');
            $table->id();
            $table->unsignedBigInteger('telegram_account_id');
            $table->string('telegram_post_url');
            $table->timestamps();

            $table->unique(['telegram_account_id', 'telegram_post_url'], 'account_post_unique');
            $table->index(['telegram_post_url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_post_views');
    }
};


