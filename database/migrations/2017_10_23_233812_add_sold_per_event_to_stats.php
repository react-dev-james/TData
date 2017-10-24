<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSoldPerEventToStats extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'stats', function ( Blueprint $table ) {
            $table->integer( 'sold_per_event' )->default( '0' )->nullable();
        } );

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table( 'stats', function ( Blueprint $table ) {
            $table->dropColumn( [ 'sold_per_event' ] );
        } );

    }

}
