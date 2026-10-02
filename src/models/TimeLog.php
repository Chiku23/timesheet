<?php

namespace Chiku\TimeSheet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeLog extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'time_logs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'project_id',
        'task_description',
        'start_time',
        'end_time',
        'duration_seconds',
        'entry_type',
        'is_edited',
    ];

    /**
     * Attribute type casts.
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
        'is_edited'  => 'boolean',
        'deleted_at' => 'datetime',
    ];

    /**
     * A time log belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A time log optionally belongs to a project.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get human-readable duration string (e.g. "45s", "15m", "2h 15m", "2h").
     */
    public function getFormattedDurationAttribute(): string
    {
        $total = (int) $this->duration_seconds;
        if ($total <= 0) {
            return '0m';
        }
        if ($total < 60) {
            return "{$total}s";
        }
        $hours = intdiv($total, 3600);
        $minutes = intdiv($total % 3600, 60);

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$minutes}m";
        }
    }
}
