<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $appends = ['duration_hours'];

    protected $fillable = [
        'work_date',
        'time_in',
        'time_out',
        'duration_minutes',
        'work_description',
        'location',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'time_in' => 'datetime',
            'time_out' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDurationHoursAttribute(): ?float
    {
        return $this->duration_minutes === null ? null : round($this->duration_minutes / 60, 2);
    }
}
