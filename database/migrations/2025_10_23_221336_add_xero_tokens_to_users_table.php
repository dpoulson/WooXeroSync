<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('xero_access_token')->nullable();
            $table->text('xero_refresh_token')->nullable();
            $table->timestamp('xero_token_expires_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['xero_access_token', 'xero_refresh_token', 'xero_token_expires_at']);
        });
    }
};
