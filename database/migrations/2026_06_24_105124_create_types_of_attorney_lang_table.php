<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTypesOfAttorneyLangTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('types_of_attorney_lang', function (Blueprint $table) {
            $table->increments('id');
            $table->string('attorney_type_name');
            $table->integer('attorney_type_id')->unsigned();
            $table->foreign('attorney_type_id')->references('attorney_type_id')->on('types_of_attorney');
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
        Schema::dropIfExists('types_of_attorney_lang');
    }
}
