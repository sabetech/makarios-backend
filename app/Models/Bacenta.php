<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bacenta extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function zone(){
        return $this->belongsTo(Zone::class);
    }

    public function leader(){
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members(){
        return $this->hasMany(Member::class);
    }

}
