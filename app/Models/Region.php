<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\SoftDeletes;
class Region extends Model
{
    use HasFactory, SoftDeletes;
    

    protected $fillable = [
        'name',
        'leader_id',
        'assistant_id',
        'stream_id',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function leader() {
        return $this->belongsTo(User::class, 'leader_id', 'id');
    }

    public function stream() {
        return $this->belongsTo(Stream::class, 'stream_id', 'id');
    }

    public function zones(){
        return $this->hasMany(Zone::class);
    }

    public function bacentas(){
        return $this->hasMany(Bacenta::class);
    }

    public function members(){
        $bacentaIds = $this->bacentas()->pluck('id')->toArray();
        return Member::whereIn('bacenta_id', $bacentaIds)->get();
    }

    public function membersThrough() {
        return $this->hasManyThrough(Member::class, Bacenta::class, 'region_id', 'bacenta_id');
    }

    public function services(){
        return $this->hasMany(Service::class, 'region_id', 'id');
    }

    public function microchurches(){
        return $this->hasMany(MicroChurch::class, 'region_id', 'id');
    }

}
