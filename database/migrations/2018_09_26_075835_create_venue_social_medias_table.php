<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVenueSocialMediasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        schema::create( 'venue_social_medias', function ( Blueprint $table ) {
            $table->increments('id');
            $table->integer('social_media_type_id');
            $table->string('url', 100)->nullable();
            $table->integer('venue_id');
            $table->timestamps();
        });

        Schema::table('venue_social_medias', function (Blueprint $table) {
            $table->unique(['venue_id', 'social_media_type_id']);
            $table->foreign('venue_id')->references('id')->on('venues')->onDelete('cascade');
            $table->foreign('social_media_type_id')->references('id')->on('social_media_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('venue_social_medias', function (Blueprint $table) {
            $table->dropForeign([
                'venue_id',
                'social_media_type_id',
            ]);
        });

        Schema::dropIfExists('venue_social_medias');
    }
}
