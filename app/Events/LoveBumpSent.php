<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoveBumpSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $coupleId;
    public $senderId;
    public $totalMeetings;

    public function __construct($coupleId, $senderId, $totalMeetings)
    {
        $this->coupleId = $coupleId;
        $this->senderId = $senderId;
        $this->totalMeetings = $totalMeetings;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('couple.' . $this->coupleId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'sender_id' => $this->senderId,
            'total_meetings' => $this->totalMeetings,
            'timestamp' => microtime(true)
        ];
    }
}
