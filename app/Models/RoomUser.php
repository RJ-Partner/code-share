<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'room_users';

    protected $fillable = [
        'room_id',
        'user_id',
        'ip',
        'user_agent',
        'is_admin',
        'last_activity',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'last_activity' => 'datetime',
    ];

    public $timestamps = true;
}
