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
            $table->string('tm_id', 50);
            $table->string('name', 1000);
            $table->string('type', 20);
            $table->string('url', 500);
            $table->string('locale', 50);
            $table->string('currency', 10);
            $table->timestamp('sales_start_datetime')->nullable();
            $table->boolean('sales_start_tbd');
            $table->date('event_local_date');
            $table->time('event_local_time');
            $table->string('event_time_zone', 50);
            $table->dateTime('event_datetime');
            $table->string('event_status_code', 50);
            $table->integer('segment_id');
            $table->integer('genre_id');
            $table->integer('sub_genre_id');
            $table->string('ticket_limit')->nullable();
            $table->integer('ticket_max_number')->nullable();
            $table->integer('event_status_id');
            $table->decimal('avg_price')->default( 0 )->nullable();
            $table->decimal('low_price')->default( 0 )->nullable();
            $table->decimal('high_price')->default( 0 )->nullable();
            $table->integer('ticket_data_id')->nullable();
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
