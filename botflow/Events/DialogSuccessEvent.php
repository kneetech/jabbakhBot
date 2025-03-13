<?php

namespace Botflow\Events;

use Botflow\Telegraph\Models\FlowState;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DialogSuccessEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public function __construct(public FlowState $state, public array $results = [])
    {
        //
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
