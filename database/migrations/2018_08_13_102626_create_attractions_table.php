<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAttractionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attractions', function ( Blueprint $table ) {
            $table->increments('id');
            $table->string('tm_id', 50);
            $table->string('name', 1000);
            $table->string('type', 100);
            $table->string('url', 500);
            $table->string('local', 50);
            $table->integer('segment_id');
            $table->integer('genre_id');
            $table->integer('sub_genre_id');
            $table->integer('upcoming_events');
            $table->integer('api_url', 500);
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
        Schema::dropIfExists('attractions');
    }
}
