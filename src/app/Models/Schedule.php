<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function schedule_place()
    {
        return $this->belongsTo(SchedulePlace::class);
    }

    public function schedule_usage()
    {
        return $this->belongsTo(ScheduleUsage::class);
    }

    public function schedule_timetable()
    {
        return $this->belongsTo(ScheduleTimetable::class);
    }

    public function reservation_status()
    {
        return $this->belongsTo(ReservationStatus::class);
    }

    public function schedule_status()
    {
        return $this->belongsTo(ScheduleStatus::class);
    }
}
