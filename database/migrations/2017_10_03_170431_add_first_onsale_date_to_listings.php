<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFirstOnsaleDateToListings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'listings', function ( Blueprint $table ) {
            $table->timestamp( 'first_onsale_date' )->nullable();
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
            $table->dropColumn( [ 'first_onsale_date' ] );
        } );

    }
}
