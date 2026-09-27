<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArrivalSettings extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'arrivals_settings';

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];
}
