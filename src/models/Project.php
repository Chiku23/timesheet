<?php

namespace Chiku\TimeSheet\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'projects';

    /**
     * Only created_at is managed; no updated_at column.
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'color_hex'];

    /**
     * A project has many time logs.
     */
    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class);
    }
}
