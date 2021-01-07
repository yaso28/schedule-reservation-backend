<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->date('ymd');
            $table->time('begins_at');
            $table->time('ends_at');
            $table->unsignedBigInteger('schedule_place_id');
            $table->unsignedBigInteger('schedule_usage_id');
            $table->unsignedBigInteger('schedule_timetable_id')->nullable();
            $table->unsignedBigInteger('reservation_status_id');
            $table->unsignedBigInteger('schedule_status_id');
            $table->timestamps();

            $table->foreign('schedule_place_id')
                ->references('id')
                ->on('schedule_places')
                ->onDelete('restrict');
            $table->foreign('schedule_usage_id')
                ->references('id')
                ->on('schedule_usages')
                ->onDelete('restrict');
            $table->foreign('schedule_timetable_id')
                ->references('id')
                ->on('schedule_timetables')
                ->onDelete('restrict');
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
        Schema::dropIfExists('schedules');
    }
}
