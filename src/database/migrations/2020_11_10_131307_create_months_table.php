<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonthsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('months', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedBigInteger('reservation_status_id');
            $table->unsignedBigInteger('schedule_status_id');
            $table->timestamps();

            $table->foreign('reservation_status_id')
                ->references('id')
                ->on('reservation_statuses')
                ->onDelete('restrict');
            $table->foreign('schedule_status_id')
                ->references('id')
                ->on('schedule_statuses')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('months');
    }
}
