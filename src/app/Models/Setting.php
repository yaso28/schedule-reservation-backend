<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $guarded = ['created_at', 'updated_at'];
    protected $primaryKey = ['category_name', 'permission_name'];
    public $incrementing = false;
    protected $keyType = 'array';

    public const KEY_MAIL_TO = 'mail_to';
    public const KEY_MAIL_SUBJECT = 'mail_subject';
    public const KEY_MAIL_MESSAGE_BEGIN = 'mail_message_begin';
    public const KEY_MAIL_MESSAGE_END = 'mail_message_end';
    public const KEY_NOTES = 'notes';
}
