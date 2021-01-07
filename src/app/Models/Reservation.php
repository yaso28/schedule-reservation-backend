<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function reservation_status()
    {
        return $this->belongsTo(ReservationStatus::class);
    }
}
