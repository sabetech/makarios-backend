<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'region_id',
        'leader_id',
    ];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }
    public function bacentas()
    {
        return $this->hasMany(Bacenta::class, 'zone_id');
    }

}
