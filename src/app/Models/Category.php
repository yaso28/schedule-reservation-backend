<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $guarded = ['created_at', 'updated_at'];
    protected $primaryKey = 'name';
    public $incrementing = false;
    protected $keyType = 'string';

    public const RESERVATION = 'reservation';
    public const RESERVATION_PUBLIC = 'reservation_public';
}
