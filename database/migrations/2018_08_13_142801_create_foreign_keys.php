<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->foreign('segment_id')->references('id')->on('segments');
            $table->foreign('genre_id')->references('id')->on('genres');
            $table->foreign('sub_genre_id')->references('id')->on('sub_genres');
            $table->foreign('event_status_id')->references('id')->on('event_statuses');
            $table->foreign('ticket_data_id')->references('id')->on('ticket_data');
        });

        Schema::table('event_prices', function (Blueprint $table) {
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
        });

        Schema::table('attraction_event', function (Blueprint $table) {
            $table->foreign('attraction_id')->references('id')->on('attractions');
            $table->foreign('event_id')->references('id')->on('events');
        });

        Schema::table('event_venue', function (Blueprint $table) {
            $table->foreign('event_id')->references('id')->on('events');
            $table->foreign('venue_id')->references('id')->on('venues');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign([
                'segment_id',
                'genre_id',
                'sub_genre_id',
                'event_status_id',
                'ticket_data_id',
            ]);
        });

        Schema::table('event_prices', function (Blueprint $table) {
            $table->dropForeign([
                'event_id',
            ]);
        });

        Schema::table('attraction_event', function (Blueprint $table) {
            $table->dropForeign([
                'attraction_id',
                'event_id',
            ]);
        });

        Schema::table('event_venue', function (Blueprint $table) {
            $table->dropForeign([
                'event_id',
                'venue_id',
            ]);
        });
    }
}
