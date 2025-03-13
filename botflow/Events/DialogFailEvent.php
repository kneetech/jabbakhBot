<?php

namespace Botflow\Events;

use Botflow\Telegraph\Models\FlowState;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DialogFailEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public function __construct(public FlowState $state, public ?int $code = 0, public ?string $reason = null)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
