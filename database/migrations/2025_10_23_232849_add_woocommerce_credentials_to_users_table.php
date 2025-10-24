<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('woocommerce_url')->nullable();
            $table->string('woocommerce_consumer_key', 100)->nullable();
            $table->string('woocommerce_consumer_secret', 100)->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['woocommerce_url', 'woocommerce_consumer_key', 'woocommerce_consumer_secret']);
        });
    }
};
