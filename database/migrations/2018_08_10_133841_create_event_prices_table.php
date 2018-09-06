<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_prices', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('event_id');
            $table->smallInteger('price_zone');
            $table->string('currency', 10);
            $table->decimal('value');
            $table->decimal('total');
            $table->timestamps();
        });

        Schema::table('event_prices', function (Blueprint $table) {
            $table->unique(['event_id', 'price_zone']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_prices');
    }
}
