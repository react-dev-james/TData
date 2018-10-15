<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCurrencyCoversionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /* We are not using this table any more */
//        Schema::create( 'currency_conversion', function ( Blueprint $table ) {
//            $table->string('code', 10);
//            $table->decimal('rate', 3, 2);
//        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('currency_conversion');
    }
}
