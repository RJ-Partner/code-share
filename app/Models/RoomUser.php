<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomUser extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'room_id',
        'user_id',
        'display_id',
        'ip',
        'user_agent',
        'is_admin',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }
}