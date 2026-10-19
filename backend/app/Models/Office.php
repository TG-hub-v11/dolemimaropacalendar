<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    protected $fillable = ['organization_id', 'parent_office_id', 'code', 'name', 'level', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
