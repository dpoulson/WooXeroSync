<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Stores a JSON map like: {"ppcp-gateway": "090", "stripe": "001"}
            $table->json('wc_payment_account_map')->nullable()->after('woocommerce_consumer_secret');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('wc_payment_account_map');
        });
    }
};
