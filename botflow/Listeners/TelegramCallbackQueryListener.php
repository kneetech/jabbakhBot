<?php

namespace Botflow\Listeners;

use Botflow\Contracts\IBotService;
use Botflow\Events\TelegramCallbackQueryReceived;
use Botflow\Telegraph\DTO\Update;

class TelegramCallbackQueryListener
{

    public function __construct(protected IBotService $botService, protected Update $update)
    {
        //
    }

    public function handle(TelegramCallbackQueryReceived $event): void
    {
        while ($flow = $this->botService->nextFlow()) {
            $flow->handleCallbackQuery($event->callbackQuery);
            $flow->handleUpdate($this->update);
        }
    }
}
