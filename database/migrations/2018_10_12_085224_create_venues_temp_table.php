<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVenuesTempTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('venues_temp', function ( Blueprint $table ) {
            $table->increments('id');
            $table->string('name');
            $table->integer('venue_id')->nullable();
            $table->integer('venue_capacity')->nullable();
            $table->decimal('venue_lat')->nullable();
            $table->decimal('venue_lng')->nullable();
            $table->string('venue_city')->nullable();
            $table->string('venue_country')->nullable();
            $table->string('venue_state')->nullable();
            $table->string('venue_zip')->nullable();
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('venues_temp');
    }
}
