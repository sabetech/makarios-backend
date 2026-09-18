<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Arrival extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bacenta_id',
        'date',
        'number_bussed',
        'time',
        'img_proof',
    ];

    public function bacenta(): BelongsTo
    {
        return $this->belongsTo(Bacenta::class, 'bacenta_id', 'id');
    }
}
