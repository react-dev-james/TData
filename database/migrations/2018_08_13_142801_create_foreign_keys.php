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
            $table->foreign('segment_id')->references('id')->on('segments')->onDelete('cascade');
            $table->foreign('genre_id')->references('id')->on('genres')->onDelete('cascade');
            $table->foreign('sub_genre_id')->references('id')->on('sub_genres')->onDelete('cascade');
            $table->foreign('data_master_id')->references('id')->on('data_master');
        });

        Schema::table('event_prices', function (Blueprint $table) {
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
        });

        Schema::table('event_attraction', function (Blueprint $table) {
            $table->foreign('attraction_id')->references('id')->on('attractions')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
        });

        Schema::table('event_venue', function (Blueprint $table) {
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('venue_id')->references('id')->on('venues')->onDelete('cascade');
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
