<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateListingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( 'listings', function ( Blueprint $table ) {
            $table->increments( 'id' );
            $table->integer('num_tickets')->default(0)->nullable();
            $table->integer('num_venues')->default(0)->nullable();
            $table->integer('num_dates')->default(0)->nullable();
            $table->decimal('avg_ticket_price')->default( 0 )->nullable();
            $table->decimal('low_ticket_price')->default( 0 )->nullable();
            $table->decimal('high_ticket_price')->default( 0 )->nullable();
            $table->decimal('total_value')->default( 0 )->nullable();
            $table->enum('source',['boxofficefox','ticketdata'])->default('boxofficefox');
            $table->string( 'status' )->default('active')->nullable();
            $table->string( 'category' )->default( "" )->nullable();
            $table->string( 'sub_category' )->default( "" )->nullable();
            $table->string( 'performer' )->default( "" )->nullable();
            $table->string( 'performer_normalized' )->default( "" )->nullable();
            $table->string( 'event_name' )->default( "" )->nullable();
            $table->string( 'event_normalized' )->default( "" )->nullable();
            $table->timestamp('event_date')->nullable();
            $table->string( 'sale_status' )->default( 'active' )->nullable();
            $table->string( 'venue' )->default( "" )->nullable();
            $table->string( 'slug' )->default( "" )->nullable();
            $table->boolean('recurring')->default(false);
            $table->integer( 'venue_capacity' )->default( 0 )->nullable();
            $table->decimal( 'venue_lat' )->default( 0 )->nullable();
            $table->decimal( 'venue_lng' )->default( 0 )->nullable();
            $table->string( 'venue_city' )->default( "" )->nullable();
            $table->string( 'venue_country' )->default( "" )->nullable();
            $table->string( 'venue_state' )->default( "" )->nullable();
            $table->string( 'venue_zip' )->default( "" )->nullable();
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
        Schema::dropIfExists( 'listings' );
    }
}
