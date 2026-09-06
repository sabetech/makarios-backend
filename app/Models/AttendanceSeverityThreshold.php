<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceSeverityThreshold extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $table = 'attendance_severity_thresholds';

    public function scopeForStreak($query, int $streak)
    {
        return $query->where('min_absences', '<=', $streak)
            ->where(function ($q) use ($streak) {
                $q->whereNull('max_absences')
                  ->orWhere('max_absences', '>=', $streak);
            });
    }
}
