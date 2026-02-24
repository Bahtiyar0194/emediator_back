<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTypesOfAgreementsLangTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('types_of_agreements_lang', function (Blueprint $table) {
            $table->increments('id');
            $table->string('agreement_type_name');
            $table->integer('agreement_type_id')->unsigned();
            $table->foreign('agreement_type_id')->references('agreement_type_id')->on('types_of_agreements');
            $table->integer('lang_id')->unsigned();
            $table->foreign('lang_id')->references('lang_id')->on('languages');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('types_of_agreements_lang');
    }
}
