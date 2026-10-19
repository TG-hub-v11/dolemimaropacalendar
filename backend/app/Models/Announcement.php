<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Announcement extends Model
{
    protected $fillable = [
        'publishing_office_id', 'author_id', 'title', 'body', 'priority',
        'requires_acknowledgment', 'published_at', 'expires_at',
    ];
    protected $casts = ['published_at' => 'datetime', 'expires_at' => 'datetime', 'requires_acknowledgment' => 'boolean'];

    public function audienceOffices(): BelongsToMany
    {
        return $this->belongsToMany(Office::class, 'announcement_audiences');
    }
}
