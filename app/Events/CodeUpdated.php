<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CodeUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $userId;
    public $userInfo;

    /**
     * Create a new event instance.
     */
    public function __construct ($roomId, $userId, $userInfo)
    {
        $this->roomId = $roomId;
        $this->userId = $userId;
        $this->userInfo = $userInfo;
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
        return 'code.updated';
    }
}