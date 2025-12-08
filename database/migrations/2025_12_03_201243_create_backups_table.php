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
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('path');
            $table->unsignedBigInteger('size')->comment('Tamanho em bytes');
            $table->enum('type', ['local', 'cloud', 'both'])->default('local');
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->string('cloud_provider')->nullable()->comment('s3, gcs, azure');
            $table->string('cloud_path')->nullable();
            $table->string('database_name');
            $table->string('mysql_version')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable()->comment('Informações adicionais');
            $table->timestamps();
            
            $table->index('status');
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
