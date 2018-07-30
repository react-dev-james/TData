<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemaneListingDataColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('listing_data', function(Blueprint $table) {
            $table->renameColumn('data_id', 'data_master_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('listing_data', function(Blueprint $table) {
            $table->renameColumn('data_master_id', 'data_id');
        });
    }
}
