<?php

namespace Botflow\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TelegramCommandReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public string $command, public string $arguments)
    {
        //
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
