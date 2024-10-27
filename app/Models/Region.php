<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $fillable = [
        'region',
        'leader_id',
        'assistant_id',
        'stream_id',
    ];

    public function zones(){
        return $this->hasMany(Zone::class);
    }
}
