<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stats', function (Blueprint $table) {
            $table->increments('id');
            $table->decimal( 'avg_ticket_price' )->default( 0 )->nullable();
            $table->integer('roi_sh')->default(0);
            $table->integer('roi_tn')->default(0);
            $table->integer('roi_low')->default(0);
            $table->integer('roi_list')->default(0);
            $table->decimal('cost')->default(0);
            $table->decimal('price')->default(0);
            $table->decimal('price_list')->default(0);
            $table->decimal('price_low')->default(0);
            $table->decimal('price_high')->default(0);
            $table->integer('sold')->default(0);
            $table->integer('available')->default(0);
            $table->integer('sh_sold')->default(0);
            $table->integer('sh_past')->default(0);
            $table->integer('tn_sold')->default(0);
            $table->integer('tn_tickets')->default(0);
            $table->integer('sh_tickets')->default(0);
            $table->integer('premium')->default(0);
            $table->integer('capacity')->default(0);
            $table->integer('volume')->default(0);
            $table->integer('rating')->default(0);
            $table->integer('listing_id')->unsigned()->nullable();
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
        Schema::dropIfExists('stats');
    }
}
