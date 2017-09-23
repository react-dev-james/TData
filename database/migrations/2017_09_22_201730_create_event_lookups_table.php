<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventLookupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( 'event_lookups', function ( Blueprint $table ) {
            $table->increments( 'id' );
            $table->string( 'event_name' )->default( "" )->nullable();
            $table->string( 'event_slug' )->default( "" )->nullable();
            $table->string( 'match_name' )->default( "" )->nullable();
            $table->string( 'match_slug' )->default( "" )->nullable();
            $table->string( 'meta' )->default( "" )->nullable();
            $table->boolean('is_auto')->default(true); //is this lookup auto added
            $table->integer( 'confidence' )->default( 0 )->nullable();
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
        Schema::dropIfExists( 'event_lookups' );
    }
}
