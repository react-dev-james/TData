<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDataMasterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('data_master', function (Blueprint $table) {
            $table->increments('id');
            $table->string('category')->default('')->nullable();
            $table->string('category_slug')->default('')->nullable();
            $table->integer('total_events')->default(0);
            $table->integer('total_sold')->default(0);
            $table->integer('total_vol')->default(0);
            $table->integer('weighted_avg')->default(0);
            $table->integer('tot_per_event')->default(0);
            $table->integer('td_events')->default(0);
            $table->integer('td_tix_sold')->default(0);
            $table->integer('td_vol')->default(0);
            $table->integer('tn_events')->default(0);
            $table->integer('tn_tix_sold')->default(0);
            $table->integer('tn_vol')->default(0);
            $table->integer('tn_avg_sale')->default(0);
            $table->integer('levi_events')->default(0);
            $table->integer('levi_tix_sold')->default(0);
            $table->integer('levi_vol')->default(0);
            $table->integer('si_events')->default(0);
            $table->integer('si_tix_sold')->default(0);
            $table->integer('si_vol')->default(0);
            $table->decimal('sfc_roi')->nullable();
            $table->integer('sfc_roi_dollar')->default(0);
            $table->integer('sfc_cogs')->default(0);
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
        Schema::table('data_master', function (Blueprint $table) {
            Schema::dropIfExists('data_master');
        });
    }
}
