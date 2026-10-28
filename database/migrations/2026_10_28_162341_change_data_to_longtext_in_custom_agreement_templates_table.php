<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDataToLongtextInCustomAgreementTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('custom_agreement_templates', function (Blueprint $table) {
            DB::statement('ALTER TABLE custom_agreement_templates MODIFY COLUMN data LONGTEXT;');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('custom_agreement_templates', function (Blueprint $table) {
            DB::statement('ALTER TABLE custom_agreement_templates MODIFY COLUMN data TEXT;');
        });
    }
}
