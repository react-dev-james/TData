<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRoiNetFieldToListings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'stats', function ( Blueprint $table ) {
            $table->float( 'roi_net' )->default( '0' )->nullable();
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
            $table->dropColumn( [ 'roi_net' ] );
        } );

    }
}
