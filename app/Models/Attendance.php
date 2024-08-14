<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'attendances';

    protected $fillable = [
        'id_number',
        'month',
        'day',
        'year',
        'is_tardy',
    ];
    use HasFactory;
}
