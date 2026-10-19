<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalendarEvent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_uuid', 'owning_office_id', 'organizer_id', 'title', 'description', 'category', 'color',
        'is_all_day', 'starts_at', 'ends_at', 'start_date', 'end_date', 'venue', 'online_link',
        'status', 'visibility', 'rrule', 'rrule_timezone', 'reminder_offsets_minutes',
    ];

    protected $casts = [
        'is_all_day' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
        'reminder_offsets_minutes' => 'array',
    ];

    public function offices(): BelongsToMany
    {
        return $this->belongsToMany(Office::class, 'event_offices');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_participants')->withPivot('response')->withTimestamps();
    }
}
