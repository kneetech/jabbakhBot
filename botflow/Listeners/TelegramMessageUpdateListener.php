<?php

namespace Botflow\Listeners;

use Botflow\Contracts\IBotService;
use Botflow\Events\TelegramMessageUpdateReceived;
use Botflow\Telegraph\DTO\Update;

class TelegramMessageUpdateListener
{

    public function __construct(protected IBotService $botService, protected Update $update)
    {
        //
    }

    public function handle(TelegramMessageUpdateReceived $event): void
    {
        while ($flow = $this->botService->nextFlow()) {
            $flow->handleEditedMessage($event->message);
            $flow->handleUpdate($this->update);
        }
    }
}
