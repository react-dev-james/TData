<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVenueCapacityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        schema::create( 'venue_capacity', function ( Blueprint $table ) {
            $table->increments('id');
            $table->integer('venue_id')->nullable();
            $table->integer('ticket_master_capacity')->nullable();
            $table->integer('box_office_fox_capacity')->nullable();
            $table->integer('song_kick_capacity')->nullable();
            $table->string('box_office_fox_name', 2000)->nullable();
            $table->integer('song_kick_id')->nullable();
            $table->string('song_kick_name', 2000)->nullable();
            $table->timestamps();
        });

        Schema::table('venue_capacity', function (Blueprint $table) {
            $table->foreign('venue_id')->references('id')->on('venues')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('venue_capacity', function (Blueprint $table) {
            $table->dropForeign([
                'venue_id',
            ]);
        });

        Schema::dropIfExists('venue_capacity');
    }
}
