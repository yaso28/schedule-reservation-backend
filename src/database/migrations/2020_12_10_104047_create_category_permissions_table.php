<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoryPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('category_permissions', function (Blueprint $table) {
            $table->string('category_name');
            $table->string('permission_name');
            $table->boolean('read_only');
            $table->timestamps();

            $table->primary(['category_name', 'permission_name']);
            $table->foreign('category_name')->references('name')->on('categories')->onDelete('restrict');
            $table->foreign('permission_name')->references('name')->on('permissions')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('category_permissions');
    }
}
