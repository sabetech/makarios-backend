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
}
