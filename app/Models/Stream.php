<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stream extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function overseer() {
        return $this->belongsTo(User::class, 'stream_overseer_id', 'id');
    }

    public function church() {
        return $this->belongsTo(Church::class, 'church_id', 'id');
    }

    public function regions() {
        return $this->hasMany(Region::class, 'stream_id', 'id');
    }

    public function members() {
        return $this->hasMany(Member::class, 'stream_id', 'id');
    }

    public function zones()
    {
        return $this->hasManyThrough(
            Zone::class,
            Region::class,
            'stream_id',
            'region_id',
            'id',
            'id'
        );
    }

    public function services(){
        return $this->hasMany(Service::class, 'stream_id', 'id');
    }
}
