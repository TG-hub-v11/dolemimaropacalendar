<?php

namespace App\Models;

use App\Models\Concerns\HasOfficeAccess;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasOfficeAccess;

    // is_active and office/role fields are deliberately NOT mass-assignable from request data.
    protected $fillable = ["name", "email", "password", "position_title"];

    protected $hidden = ["password", "remember_token"];

    protected function casts(): array
    {
        return [
            "email_verified_at" => "datetime",
            "password" => "hashed",
            "is_active" => "boolean",
            "deactivated_at" => "datetime",
        ];
    }
}
