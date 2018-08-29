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
        schema::create( 'social_media', function ( Blueprint $table ) {
            $table->increments('id');
            $table->string('name', 100);
            $table->integer('attraction_id');
            $table->timestamps();
        });

        Schema::table('social_media', function (Blueprint $table) {
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
        Schema::table('social_media', function (Blueprint $table) {
            $table->dropForeign([
                'attraction_id',
            ]);
        });

        Schema::dropIfExists('social_media');
    }
}
