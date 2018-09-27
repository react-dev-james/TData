<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEventStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->tinyInteger('event_state_id')->nullable();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreign('event_state_id')->references('id')->on('event_states');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign([
                'event_state_id',
            ]);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('event_state_id');
        });
    }
}
