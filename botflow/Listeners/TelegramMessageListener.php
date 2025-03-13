<?php

namespace Botflow\Listeners;

use Botflow\Contracts\IBotService;
use Botflow\Events\TelegramMessageReceived;
use Botflow\Exceptions\Exception;
use Botflow\Telegraph\DTO\Update;

class TelegramMessageListener
{

    public function __construct(protected IBotService $botService, protected Update $update)
    {
        //
    }

    /**
     * @throws Exception
     */
    public function handle(TelegramMessageReceived $event): void
    {
        while ($flow = $this->botService->nextFlow()) {
            $flow->handleChatMessage($event->message);
            $flow->handleUpdate($this->update);
        }
    }
}
