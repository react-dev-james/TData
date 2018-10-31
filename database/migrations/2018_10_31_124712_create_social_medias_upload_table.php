<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSocialMediasUploadTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        schema::create( 'social_medias_upload', function ( Blueprint $table ) {
            $table->integer('attraction_id')->nullable();

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

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('social_medias_upload');
    }
}
