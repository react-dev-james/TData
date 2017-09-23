<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('data', function (Blueprint $table) {
            $table->increments('id');
            $table->string('category')->default('')->nullable();
            $table->string('category_slug')->default('')->nullable();
            $table->string('name')->default('')->nullable();
            $table->string('name_slug')->default('')->nullable();
            $table->integer('upcoming_events')->default(0);
            $table->integer('past_events')->default(0);
            $table->integer('avg_sale_price')->default(0);
            $table->integer('avg_sale_price_past')->default(0);
            $table->integer('avg_quantity')->default(0);
            $table->integer('avg_quantity_past')->default(0);
            $table->integer('volume_past')->default(0);
            $table->integer('yesterday_sales')->default(0);
            $table->integer('total_sales')->default(0);
            $table->integer('total_sales_past')->default(0);
            $table->integer('total_listed')->default(0);
            $table->integer( 'avg_listed' )->default( 0 );
            $table->enum( 'source', [ 'boxofficefox', 'ticketdata' ] )->default( 'ticketdata' );
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
        Schema::dropIfExists('data');
    }
}
