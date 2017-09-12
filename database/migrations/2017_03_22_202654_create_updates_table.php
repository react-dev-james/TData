<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create( 'updates', function ( Blueprint $table ) {
            $table->increments( 'id' );
            $table->integer( 'listing_id' )->unsigned()->index()->nullable();
            $table->timestamp( "listings_at" )->default( null )->nullable()->index();
            $table->timestamp( "stats_at" )->default( null )->nullable()->index();
            $table->integer( 'stats_times' )->default( 0 )->nullable();
            $table->timestamps();
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists( 'updates' );
    }
}
