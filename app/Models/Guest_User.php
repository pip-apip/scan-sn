<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guest_User extends Model
{
    protected $table = 'guest_users';
    protected $fillable = [
        'uuid',
        'name',
        'current_location',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];
}
