<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryPermission extends Model
{
    use HasFactory;

    protected $guarded = ['created_at', 'updated_at'];
    protected $primaryKey = ['category_name', 'permission_name'];
    public $incrementing = false;
    protected $keyType = 'array';

    public const READ = 'read';
    public const WRITE = 'write';
}
