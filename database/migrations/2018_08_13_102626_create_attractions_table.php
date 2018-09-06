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
            $table->string('type', 100)->nullable();
            $table->string('url', 1024)->nullable();
            $table->string('locale', 50)->nullable();
            $table->integer('segment_id')->nullable();
            $table->integer('genre_id')->nullable();
            $table->integer('sub_genre_id')->nullable();
            $table->integer('upcoming_events')->nullable();
            $table->string('api_url', 1024)->nullable();
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
