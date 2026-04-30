<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediationContracts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mediation_contracts', function (Blueprint $table) {
            $table->increments('mediation_contract_id');
            $table->uuid('uuid')->unique();
            $table->integer('agreement_id')->unsigned();
            $table->foreign('agreement_id')->references('agreement_id')->on('agreements')->onDelete('cascade');
            
            $table->text('data')->nullable();
            $table->string('sigex_document_id')->nullable();

            $table->integer('status_type_id')->unsigned();
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
        Schema::dropIfExists('mediation_contracts');
    }
}