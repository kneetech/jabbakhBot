<?php

namespace Botflow\Events;

use DefStudio\Telegraph\DTO\CallbackQuery;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TelegramCallbackQueryReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public CallbackQuery $callbackQuery)
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
