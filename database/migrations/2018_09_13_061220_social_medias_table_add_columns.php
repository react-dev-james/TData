<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SocialMediasTableAddColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('social_medias', function (Blueprint $table) {
            $table->integer('seatgeek_id')->nullable();
            $table->string('seatgeek_name', 1000)->nullable();
            $table->float('seatgeek_score', 8, 2)->nullable();

            $table->string('spotify_name', 1000)->nullable();
            $table->string('spotify_id', 1000)->nullable();
            $table->integer('spotify_followers')->nullable();
            $table->integer('spotify_popularity')->nullable();

            $table->string('lastfm_name', 1000)->nullable();
            $table->integer('lastfm_listeners')->nullable();

            $table->integer('nextbigsound_id')->nullable();
            $table->string('nextbigsound_name', 1000)->nullable();
            $table->bigInteger('nextbigsound_listeners')->nullable();
            $table->bigInteger('nextbigsound_streams')->nullable();
            $table->string('nextbigsound_stage', 1000)->nullable();
            $table->string('nexbigsound_engagement', 1000)->nullable();
            $table->integer('nextbigsound_facebook_likes')->nullable();
            $table->integer('nextbigsound_twitter_followers')->nullable();
            $table->integer('nextbigsound_songkick_followers')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('social_medias', function (Blueprint $table) {
            $table->dropColumn('seatgeek_id');
            $table->dropColumn('seatgeek_name');
            $table->dropColumn('seatgeek_score');

            $table->dropColumn('spotify_name');
            $table->dropColumn('spotify_id');
            $table->dropColumn('spotify_followers');
            $table->dropColumn('spotify_popularity');

            $table->dropColumn('lastfm_name');
            $table->dropColumn('lastfm_listeners');

            $table->dropColumn('nextbigsound_id');
            $table->dropColumn('nextbigsound_name');
            $table->dropColumn('nextbigsound_listeners');
            $table->dropColumn('nextbigsound_streams');
            $table->dropColumn('nextbigsound_stage');
            $table->dropColumn('nexbigsound_engagement');
            $table->dropColumn('nextbigsound_facebook_likes');
            $table->dropColumn('nextbigsound_twitter_followers');
            $table->dropColumn('nextbigsound_songkick_followers');
        });
    }
}
