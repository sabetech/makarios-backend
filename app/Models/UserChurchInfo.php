<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserChurchInfo extends Model
{
    use HasFactory;
    protected $table = 'users_church_info';

    protected $fillable = [
        'user_id',
        'church_id',
        'stream_id',
        'region_id',
        'zone_id',
        'bacenta_id',
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
