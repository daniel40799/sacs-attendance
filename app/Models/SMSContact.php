<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SMSContact extends Model
{
    protected $table = 'sms_contacts';

    protected $fillable = [
        'id_number',
        'first_name',
        'last_name',
        'grade_level',
        'section',
        'contact_number',
    ];
    use HasFactory;
}
