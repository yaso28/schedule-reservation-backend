<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasFactory;

    public const RESERVATION_READ = 'reservation.read';
    public const RESERVATION_WRITE = 'reservation.write';
}
