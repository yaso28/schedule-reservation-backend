<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchedulePlacesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('schedule_places', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('abbreviation');
            $table->unsignedInteger('price_per_hour');
            $table->unsignedInteger('order_reverse')->default(0);
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
        Schema::dropIfExists('schedule_places');
    }
}
