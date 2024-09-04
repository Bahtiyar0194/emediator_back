<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('user_id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('given_name')->nullable();
            $table->string('iin')->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->integer('city_id')->unsigned()->nullable();
            $table->foreign('city_id')->references('city_id')->on('cities');
            $table->float('balance')->default(0);
            $table->string('avatar')->nullable();
            $table->integer('current_role_id')->unsigned()->default(1);
            $table->foreign('current_role_id')->references('role_type_id')->on('types_of_user_roles');
            $table->integer('lang_id')->unsigned()->nullable();
            $table->foreign('lang_id')->references('lang_id')->on('languages');
            $table->integer('status_type_id')->default(3)->unsigned();
            $table->foreign('status_type_id')->references('status_type_id')->on('types_of_status');
            $table->string('sms_hash')->nullable();
            $table->string('email_hash')->nullable();
            $table->string('password')->nullable();
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
        Schema::dropIfExists('users');
    }
}
