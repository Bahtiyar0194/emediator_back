<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTypesOfAgreementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('types_of_agreements', function (Blueprint $table) {
            $table->increments('agreement_type_id');
            $table->string('agreement_slug');

            $table->integer('parent_id')->unsigned()->nullable();
            $table->foreign('parent_id')->references('agreement_type_id')->on('types_of_agreements')->onDelete('set null');
            
            $table->integer('show_status_id')->default(1)->unsigned();
            $table->foreign('show_status_id')->references('show_status_id')->on('show_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('types_of_agreements');
    }
}
