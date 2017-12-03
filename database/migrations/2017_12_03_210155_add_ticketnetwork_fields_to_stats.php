<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTicketnetworkFieldsToStats extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'stats', function ( Blueprint $table ) {
            $table->integer( 'tix_sold_in_date_range' )->default( '0' )->nullable();
            $table->float( 'avg_sold_price_in_date_range' )->default( '0' )->nullable();
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
            $table->dropColumn( [ 'tix_sold_in_date_range', 'avg_sold_price_in_date_range' ] );
        } );

    }
}
