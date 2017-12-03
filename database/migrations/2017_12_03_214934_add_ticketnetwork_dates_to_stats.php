<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTicketnetworkDatesToStats extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'stats', function ( Blueprint $table ) {
            $table->integer( 'tn_events' )->default( '0' )->nullable();
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
            $table->dropColumn( [ 'tn_events'] );
        } );

    }
}
