<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateListingDataPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( 'listing_data', function ( Blueprint $table ) {
            $table->integer( 'listing_id' )->unsigned()->index();
            $table->foreign( 'listing_id' )->references( 'id' )->on( 'listings' )->onDelete( 'cascade' );
            $table->integer( 'data_id' )->unsigned()->index();
            $table->foreign( 'data_id' )->references( 'id' )->on( 'data' )->onDelete( 'cascade' );
            $table->integer('confidence')->default()->nullable();
            $table->primary( [ 'listing_id', 'data_id' ] );
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop( 'listing_data' );
    }
}
