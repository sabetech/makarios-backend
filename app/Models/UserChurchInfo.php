<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserChurchInfo extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'users_church_info';

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

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
    public function church() {
        return $this->belongsTo(Church::class, 'church_id', 'id');
    }
    public function stream() {
        return $this->belongsTo(Stream::class, 'stream_id', 'id');
    }
    public function region() {
        return $this->belongsTo(Region::class, 'region_id', 'id');
    }
    public function zone() {
        return $this->belongsTo(Zone::class, 'zone_id', 'id');
    }
    public function bacenta() {
        return $this->belongsTo(Bacenta::class, 'bacenta_id', 'id');
    }
}
