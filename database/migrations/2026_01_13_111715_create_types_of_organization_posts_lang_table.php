<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTypesOfOrganizationPostsLangTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('types_of_organization_posts_lang', function (Blueprint $table) {
            $table->increments('id');
            $table->string('post_type_name');
            $table->integer('post_type_id')->unsigned();
            $table->foreign('post_type_id')->references('post_type_id')->on('types_of_organization_posts')->onDelete('cascade');
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
        Schema::dropIfExists('types_of_organization_posts_lang');
    }
}
