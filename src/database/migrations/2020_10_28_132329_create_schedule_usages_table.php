<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScheduleUsagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('schedule_usages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_public');
            $table->unsignedBigInteger('reservation_organization_id');
            $table->unsignedInteger('order_reverse')->default(0);
            $table->timestamps();

            $table->foreign('reservation_organization_id')
                ->references('id')
                ->on('reservation_organizations')
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
        Schema::dropIfExists('schedule_usages');
    }
}
