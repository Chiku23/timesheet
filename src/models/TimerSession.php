<?php

namespace Chiku\TimeSheet\Models;

use Illuminate\Database\Eloquent\Model;

class TimerSession extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'timer_sessions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'project_id',
        'task_description',
        'status',
        'session_started_at',
        'segment_started_at',
        'accumulated_seconds',
    ];

    /**
     * Attribute type casts.
     */
    protected $casts = [
        'session_started_at' => 'datetime',
        'segment_started_at' => 'datetime',
        'accumulated_seconds' => 'integer',
    ];

    /**
     * Association with user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Association with project.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Calculate current live seconds based on server clock.
     */
    public function getCurrentSecondsAttribute(): int
    {
        $accumulated = (int) $this->accumulated_seconds;

        if ($this->status === 'running' && $this->segment_started_at) {
            $now = time();
            $segmentStart = $this->segment_started_at->getTimestamp();
            $diff = max(0, $now - $segmentStart);
            return $accumulated + $diff;
        }

        return $accumulated;
    }
}
