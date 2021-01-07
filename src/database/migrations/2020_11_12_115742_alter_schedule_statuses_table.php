<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AlterScheduleStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('schedule_statuses', function (Blueprint $table) {
            $table->string('display_type')->nullable()->change();
            $table->dropColumn('fixed');
            $table->unsignedTinyInteger('bulk_change_mode')->after('is_public');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('schedule_statuses')->whereNull('display_type')->update(['display_type' => '']);

        Schema::table('schedule_statuses', function (Blueprint $table) {
            $table->dropColumn('bulk_change_mode');
            $table->boolean('fixed')->after('display_type');
            $table->string('display_type')->nullable(false)->change();
        });
    }
}
