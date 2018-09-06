<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function ( Blueprint $table ) {
            $table->increments( 'id' );
            $table->string('tm_id', 100);
            $table->string('name', 2000);
            $table->string('type', 1000);
            $table->string('url', 1024)->nullable();
            $table->string('locale', 50)->nullable();
            $table->string('currency', 10)->nullable();
            $table->timestamp('public_sale_datetime')->nullable();
            $table->boolean('sales_start_tbd')->nullable();
            $table->date('event_local_date')->nullable();
            $table->time('event_local_time')->nullable();
            $table->string('event_time_zone', 50)->nullable();
            $table->dateTime('event_datetime')->nullable();
            $table->string('event_status_code', 50)->nullable();
            $table->integer('segment_id')->nullable();
            $table->integer('genre_id')->nullable();
            $table->integer('sub_genre_id')->nullable();
            $table->string('ticket_limit', 1000)->nullable();
            $table->integer('ticket_max_number')->nullable();
            $table->decimal('avg_price')->default( 0 )->nullable();
            $table->decimal('low_price')->default( 0 )->nullable();
            $table->decimal('high_price')->default( 0 )->nullable();
            $table->integer('data_master_id')->nullable();
            $table->string('presale_name', 1000)->nullable();
            $table->dateTime('presale_datetime')->nullable();
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
        Schema::dropIfExists( 'events' );
    }
}
