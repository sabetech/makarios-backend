<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Antibrutish extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['created_at', 'updated_at'];
    protected $table = 'antibrutish';

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function shepherdorialCycle()
    {
        return $this->belongsTo(ShepherdorialCycle::class);
    }
}
