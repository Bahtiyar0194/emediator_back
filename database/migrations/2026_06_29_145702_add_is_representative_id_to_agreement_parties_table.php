<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsRepresentativeIdToAgreementPartiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('agreement_parties', function (Blueprint $table) {
            $table->integer('representative_id')->unsigned()->nullable()->after('is_mediator');
            $table->foreign('representative_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('agreement_parties', function (Blueprint $table) {
            //
        });
    }
}
