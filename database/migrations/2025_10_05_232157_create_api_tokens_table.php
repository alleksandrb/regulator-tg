<?php

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
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Название токена для идентификации
            $table->string('token', 64)->unique(); // Сам токен
            $table->text('description')->nullable(); // Описание для чего используется
            $table->boolean('is_active')->default(true); // Активен ли токен
            $table->timestamp('last_used_at')->nullable(); // Когда последний раз использовался
            $table->timestamp('expires_at')->nullable(); // Срок действия (опционально)
            $table->timestamps();
            
            $table->index(['token', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
    }
};
