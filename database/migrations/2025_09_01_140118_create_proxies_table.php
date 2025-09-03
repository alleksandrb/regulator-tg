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
        Schema::create('proxies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('ip');
            $table->integer('port');
            $table->string('protocol')->nullable();
            $table->string('login');
            $table->string('password');
            $table->text('refresh_link');
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('max_accounts')->default(10);
            $table->timestamps();
            
            $table->index(['is_active', 'usage_count']);
            $table->unique(['ip', 'port', 'login', 'password'], 'unique_proxy_endpoint');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proxies');
    }
};
