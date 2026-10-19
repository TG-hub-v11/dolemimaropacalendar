<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskActivity extends Model
{
    public $timestamps = false;
    protected $fillable = ['task_id', 'user_id', 'type', 'from_value', 'to_value', 'body', 'progress_percent'];
}
