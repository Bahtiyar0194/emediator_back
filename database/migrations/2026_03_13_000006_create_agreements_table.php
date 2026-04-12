<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgreementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agreements', function (Blueprint $table) {
            $table->increments('agreement_id');
            $table->uuid('uuid')->unique();

            $table->integer('initiator_id')->unsigned();
            $table->foreign('initiator_id')->references('user_id')->on('users')->onDelete('cascade');

            $table->json('data')->nullable(); // все значения

            $table->string('sigex_document_id')->nullable();

            $table->integer('agreement_type_id')->unsigned();
            $table->foreign('agreement_type_id')->references('agreement_type_id')->on('types_of_agreements');

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
        Schema::dropIfExists('agreements');
    }
}
