<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTmIdIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->index('tm_id');
        });

        Schema::table('attractions', function (Blueprint $table) {
            $table->index('tm_id');
        });

        Schema::table('venues', function (Blueprint $table) {
            $table->index('tm_id');
        });

        Schema::table('event_venue', function (Blueprint $table) {
            $table->index(['event_id', 'venue_id']);
        });

        Schema::table('genres', function (Blueprint $table) {
            $table->index('tm_id');
        });

        Schema::table('sub_genres', function (Blueprint $table) {
            $table->index('tm_id');
        });

        Schema::table('event_prices', function (Blueprint $table) {
            $table->index('event_id');
        });

        Schema::table('segments', function (Blueprint $table) {
            $table->index('tm_id');
        });

        Schema::table('social_media', function (Blueprint $table) {
            $table->index('attraction_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // -- TODO -- drop indexes
    }
}
