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

    public function stream() {
        return $this->hasOneThrough(Stream::class, Region::class, 'id', 'id', 'region_id', 'stream_id');
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }
    public function bacentas()
    {
        return $this->hasMany(Bacenta::class, 'zone_id');
    }

    public function members() {
        $bacentaIds = $this->bacentas()->pluck('id')->toArray();
        return Member::whereIn('bacenta_id', $bacentaIds)->get();
    }

    public function services() {
        return $this->hasMany(Service::class, 'zone_id', 'id');
    }

}
