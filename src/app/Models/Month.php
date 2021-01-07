<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Services\FormatService;

class Month extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function reservation_status()
    {
        return $this->belongsTo(ReservationStatus::class);
    }

    public function schedule_status()
    {
        return $this->belongsTo(ScheduleStatus::class);
    }

    public function getNameAttribute()
    {
        return __('date.year', ['year' => $this->year]) . __('date.month', ['month' => sprintf('%02d', $this->month)]);
    }

    public function getFirstDayAttribute()
    {
        return Carbon::create($this->year, $this->month, 1)->format(FormatService::DATE_FORMAT);
    }

    public function getLastDayAttribute()
    {
        return Carbon::create($this->year, $this->month, 1)->lastOfMonth()->format(FormatService::DATE_FORMAT);
    }
}
