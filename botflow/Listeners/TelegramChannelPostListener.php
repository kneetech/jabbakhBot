<?php

namespace Botflow\Listeners;

use Botflow\Contracts\IBotService;
use Botflow\Events\TelegramChannelPostReceived;
use Botflow\Events\TelegramMessageReceived;
use Botflow\Exceptions\Exception;
use Botflow\Telegraph\DTO\Update;

class TelegramChannelPostListener
{

    public function __construct(protected IBotService $botService, protected Update $update)
    {
        //
    }

    /**
     * @throws Exception
     */
    public function handle(TelegramChannelPostReceived $event): void
    {
        while ($flow = $this->botService->nextFlow()) {
            $flow->handleChannelPost($event->message);
            $flow->handleUpdate($this->update);
        }
    }
}
