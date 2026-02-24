<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTypesOfOrganizationsLangTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('types_of_organizations_lang', function (Blueprint $table) {
            $table->increments('id');
            $table->string('organization_type_name');
            $table->integer('organization_type_id')->unsigned();
            $table->foreign('organization_type_id')->references('organization_type_id')->on('types_of_organizations')->onDelete('cascade');
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
        Schema::dropIfExists('types_of_organizations_lang');
    }
}
