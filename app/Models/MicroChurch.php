<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MicroChurch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'stream_id',
        'region_id',
        'leader_id',
        'location',
    ];

    public function stream()
    {
        return $this->belongsTo(Stream::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function leader()
    {
        return $this->belongsTo(User::class);
    }

    public function members()
    {
        return $this->hasMany(Member::class, 'micro_churches_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }
}
