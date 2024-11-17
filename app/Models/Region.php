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

}
