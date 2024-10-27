<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Church extends Model
{
    use HasFactory;

    protected $table = 'churches';

    protected $fillable = [
        'name',
        'head_pastor',
        'user_id',
        'img-url',
        'description',
        'location_id',
    ];

    public function streams() {
        return $this->hasMany(Stream::class, 'church_id', 'id');
    }

}
