<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeMembership extends Model
{
    protected $fillable = ['user_id', 'office_id', 'role_id', 'is_primary', 'granted_by'];
    protected $casts = ['is_primary' => 'boolean'];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
