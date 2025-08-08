<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserActivity implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $activeUsers;
    public $activityType; // join, leave, update

    /**
     * Create a new event instance.
     */
    public function __construct($roomId, $activeUsers, $activityType)
    {
        $this->roomId = $roomId;
        $this->activeUsers = $activeUsers;
        $this->activityType = $activityType;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return new Channel('room.' . $this->roomId);
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs()
    {
        return 'user.activity';
    }
}