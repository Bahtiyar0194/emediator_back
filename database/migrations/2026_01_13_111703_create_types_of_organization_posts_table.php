<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTypesOfOrganizationPostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('types_of_organization_posts', function (Blueprint $table) {
            $table->increments('post_type_id');
            $table->string('post_type_slug');
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
        Schema::dropIfExists('types_of_organization_posts');
    }
}
