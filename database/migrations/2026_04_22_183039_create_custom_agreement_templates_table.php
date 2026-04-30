<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomAgreementTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_agreement_templates', function (Blueprint $table) {
            $table->increments('template_id');

            $table->string('template_name');

            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');

            $table->text('data')->nullable();

            $table->integer('status_type_id')->unsigned()->default(1);
            $table->foreign('status_type_id')->references('status_type_id')->on('types_of_status');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('custom_agreement_templates');
    }
}
