<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAttractionSocialMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        schema::create( 'social_medias', function ( Blueprint $table ) {
            $table->increments('id');
            $table->integer('attraction_id');
            $table->timestamps();
        });

        Schema::table('social_medias', function (Blueprint $table) {
            $table->unique(['attraction_id']);
            $table->foreign('attraction_id')->references('id')->on('attractions')->onDelete('cascade');
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
            $table->dropForeign([
                'attraction_id',
                'social_media_type_id',
            ]);
        });

        Schema::dropIfExists('social_medias');
    }
}
