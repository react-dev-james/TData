<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventPresalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( 'event_presales', function ( Blueprint $table ) {
            $table->increments('id');
            $table->integer('event_id');
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('event_presales', function (Blueprint $table) {
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_presales');
    }
}
