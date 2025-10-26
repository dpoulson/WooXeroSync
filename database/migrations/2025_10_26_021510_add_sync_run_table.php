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
        Schema::create('sync_runs', function (Blueprint $table) {
            $table->id();
            // Link the run to the user who initiated it
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('status')->index(); // 'Running', 'Success', 'Failure'
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            
            $table->integer('total_orders')->default(0);
            $table->integer('successful_invoices')->default(0);
            $table->integer('failed_invoices')->default(0);
            
            // Store details of any critical failure or individual batch errors
            $table->json('error_details')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_runs');
    }
};