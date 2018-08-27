<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTicketUrlToListings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'listings', function ( Blueprint $table ) {
            $table->string( 'ticket_url' )->default( '' )->nullable();
            $table->string( 'date_hash' )->default( '' )->nullable();
        } );

        //DB::statement( 'ALTER TABLE `listings` ADD INDEX `Listings_Date_Index` (`date_hash`);' );


        /* ALTER TABLE `listings`
MODIFY COLUMN `ticket_url`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL AFTER `updated_at`; */

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table( 'listings', function ( Blueprint $table ) {
            $table->dropColumn( [ 'ticket_url','date_hash' ] );
        } );

    }
}
