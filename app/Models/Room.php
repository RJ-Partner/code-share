<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'room_id',
        'password',
        'is_public',
        'read_only',
        'is_active',
        'code'
    ];

    public function users()
    {
        return $this->hasMany(RoomUser::class, 'room_id', 'room_id');
    }
}