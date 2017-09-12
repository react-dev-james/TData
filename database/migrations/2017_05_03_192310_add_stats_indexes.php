<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatsIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /*
        DB::statement( 'ALTER TABLE `stats`
ADD INDEX `Stats_Revenue` (`projected_revenue`) ,
ADD INDEX `Stats_Per_Bed` (`price_per_bed`) ,
ADD INDEX `Stats_Occupancy` (`percent_booked`) ,
ADD INDEX `Stats_Rate` (`avg_rate`) ;' );
        */
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
