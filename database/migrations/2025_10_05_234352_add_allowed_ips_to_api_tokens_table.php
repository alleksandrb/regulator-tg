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
        Schema::table('api_tokens', function (Blueprint $table) {
            $table->json('allowed_ips')->nullable()->after('expires_at')
                ->comment('JSON массив разрешенных IP-адресов. Если null - доступ разрешен с любых IP');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_tokens', function (Blueprint $table) {
            $table->dropColumn('allowed_ips');
        });
    }
};
