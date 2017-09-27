<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAdjustmentsFlagToDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'data', function ( Blueprint $table ) {
            $table->boolean('adjusted')->default(false)->nullable();
        } );

        Schema::table( 'listings', function ( Blueprint $table ) {
            $table->boolean( 'adjusted' )->default( false )->nullable();
        } );

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table( 'data', function ( Blueprint $table ) {
            $table->dropColumn( [ 'adjusted' ] );
        } );
        Schema::table( 'listings', function ( Blueprint $table ) {
            $table->dropColumn( [ 'adjusted' ] );
        } );

    }
}
