<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleStatus extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public const DISPLAY_TYPE_WARNING = 'warning';
    public const DISPLAY_TYPE_DANGER = 'danger';

    public const BULK_CHANGE_NONE = 0;
    public const BULK_CHANGE_FROM = 1;
    public const BULK_CHANGE_TO = 2;
}
