<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVenuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('venues', function ( Blueprint $table ) {
            $table->increments('id');
            $table->string('tm_id', 50);
            $table->string('name', 1000);
            $table->string('url', 1024)->nullable();
            $table->string('locale', 50)->nullable();
            $table->string('postal_code', 50)->nullable();
            $table->string('time_zone', 50)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state_name', 100)->nullable();
            $table->string('state_code', 5)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('address', 500)->nullable();
            $table->float('longitude')->nullable();
            $table->float('latitude')->nullable();
            $table->string('api_url', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('venues');
    }
}
