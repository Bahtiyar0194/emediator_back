<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediationContractParties extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mediation_contract_parties', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->integer('mediation_contract_id')->unsigned();
            $table->foreign('mediation_contract_id')->references('mediation_contract_id')->on('mediation_contracts')->onDelete('cascade');
            $table->boolean('is_mediator')->default(0);
            $table->string('sigex_sign_id')->nullable();
            $table->text('sigex_sign')->nullable();
            $table->timestamp('signed_at')->nullable();
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
        Schema::dropIfExists('mediation_contract_parties');
    }
}
