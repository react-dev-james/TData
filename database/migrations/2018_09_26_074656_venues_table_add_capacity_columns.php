<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class VenuesTableAddCapacityColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->integer('ticket_master_capacity')->nullable();
            $table->integer('box_office_fox_capacity')->nullable();
            $table->integer('song_kick_capacity')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn('ticket_master_capacity');
            $table->dropColumn('box_office_fox_capacity');
            $table->dropColumn('song_kick_capacity');
        });
    }
}
