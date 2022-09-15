<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('origin_address_id');
            $table->unsignedInteger('arrival_address_id');
            $table->unsignedInteger('status_id')->default(1);
            $table->unsignedInteger('client_id');
            $table->decimal('product_weight')->unsigned();
            $table->unsignedInteger('product_amount');
            $table->decimal('value')->unsigned();
            $table->timestamps();
        });

        Schema::table('orders', function($table) {
            $table->foreign('origin_address_id')->references('id')->on('addresses');
            $table->foreign('arrival_address_id')->references('id')->on('addresses');
            $table->foreign('status_id')->references('id')->on('statuses');
            $table->foreign('client_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
