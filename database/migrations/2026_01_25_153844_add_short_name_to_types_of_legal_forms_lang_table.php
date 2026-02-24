<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShortNameToTypesOfLegalFormsLangTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('types_of_legal_forms_lang', function (Blueprint $table) {
            $table->string('short_name')->after('legal_form_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('types_of_legal_forms_lang', function (Blueprint $table) {
            //
        });
    }
}
