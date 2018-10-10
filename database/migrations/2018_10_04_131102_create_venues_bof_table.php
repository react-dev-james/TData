<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVenuesBofTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('venues_bof', function ( Blueprint $table ) {
            $table->increments('id');
            $table->integer('venue_id')->nullable();
            $table->string('name');
            $table->integer('capacity')->nullable();
            $table->decimal('lat')->nullable();
            $table->decimal('lng')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->timestamps();
        } );

        Schema::table('venues_bof', function (Blueprint $table) {
            $table->foreign('venue_id')->references('id')->on('venues');
            $table->unique('venue_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('venues_bof', function (Blueprint $table) {
            $table->dropForeign([
               'venue_id',
            ]);
        });

        Schema::dropIfExists('venues_bof');
    }
}
