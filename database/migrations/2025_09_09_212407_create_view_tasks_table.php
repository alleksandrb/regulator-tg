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
        Schema::create('view_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('telegram_post_url');
            $table->integer('views_count');
            $table->foreignId('user_id');
            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable();
            
            $table->index(['created_at', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('view_tasks');
    }
};
