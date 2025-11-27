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
        Schema::table('melhor_envio_settings', function (Blueprint $table) {
            $table->text('bearer_token')->nullable()->after('client_secret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('melhor_envio_settings', function (Blueprint $table) {
            $table->dropColumn('bearer_token');
        });
    }
};
