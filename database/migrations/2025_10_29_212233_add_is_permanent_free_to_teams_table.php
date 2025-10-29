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
        // We are modifying an existing table, so use Schema::table()
        Schema::table('teams', function (Blueprint $table) {
            // Add a new boolean column, default to false (not permanently free)
            // It should be nullable if you want to use it as a ternary check, 
            // but setting a default of false is usually cleaner.
            $table->boolean('is_permanent_free')->default(false)->after('personal_team');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // When rolling back, we drop the column we added
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('is_permanent_free');
        });
    }
};
