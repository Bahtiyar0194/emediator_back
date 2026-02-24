<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTypesOfLegalFormsLangTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('types_of_legal_forms_lang', function (Blueprint $table) {
            $table->increments('id');
            $table->string('legal_form_name');
            $table->integer('legal_form_id')->unsigned();
            $table->foreign('legal_form_id')->references('legal_form_id')->on('types_of_legal_forms');
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
        Schema::dropIfExists('types_of_legal_forms_lang');
    }
}
