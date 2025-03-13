<?php

namespace App\Models\Bot\Flows;

use Botflow\Contracts\CommonBotFlow;
use Botflow\Helpers\JSON;
use DefStudio\Telegraph\DTO\CallbackQuery;
use DefStudio\Telegraph\DTO\Message;

class TestFlow extends CommonBotFlow
{

    public function handleChatMessage(Message $message): void
    {

    }

    public function handleCallbackQuery(CallbackQuery $callbackQuery): void
    {
        $this->botService->telegraph()
            ->markdown("'''\n" . json_encode($callbackQuery->data(), JSON::CONSOLE) . '\n```')
            ->send();
    }
}
