<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_uuid', 'responsible_office_id', 'creator_id', 'assignee_id', 'reviewer_id',
        'calendar_event_id', 'meeting_id', 'title', 'description', 'priority', 'requires_review',
        'start_date', 'due_date',
    ];
    // status, review_status, completed_at, version are NOT mass assignable: only TaskService changes them.

    protected $casts = [
        'status' => TaskStatus::class,
        'requires_review' => 'boolean',
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class);
    }
}
