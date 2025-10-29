<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_run_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sync_run_id')->constrained()->onDelete('cascade'); // Link to the SyncRun
            $table->string('level'); // e.g., 'info', 'error', 'debug'
            $table->text('message'); // The actual log message
            $table->json('context')->nullable(); // Optional: store Monolog context data
            $table->timestamps();
            
            // Optional: Index for faster lookup by run and time
            $table->index(['sync_run_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_run_logs');
    }
};