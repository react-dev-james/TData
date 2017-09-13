<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( 'sales', function ( Blueprint $table ) {
            $table->increments( 'id' );
            $table->boolean( 'is_future' )->default( false )->nullbale();
            $table->boolean( 'manual' )->default( false )->nullbale();
            $table->string( 'type' )->default( "" )->nullable();
            $table->string( 'day' )->default( "" )->nullable();
            $table->string( 'offer_code' )->default( "" )->nullable();
            $table->timestamp( 'sale_date' )->nullable();
            $table->integer( 'listing_id' )->unsigned()->nullable();
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
        Schema::dropIfExists( 'sales' );
    }
}
