<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWeightSoldAndTotals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'listings', function ( Blueprint $table ) {
            $table->integer( 'weighted_sold' )->default( '0' )->nullable();
            $table->integer( 'total_sold_all' )->default( '0' )->nullable(); //total sold for all networks
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table( 'listings', function ( Blueprint $table ) {
            $table->dropColumn( [ 'weighted_sold', 'total_sold_all' ] );
        } );

    }
}
