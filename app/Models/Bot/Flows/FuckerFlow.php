<?php

namespace App\Models\Bot\Flows;

use Botflow\Contracts\CommonBotFlow;
use DefStudio\Telegraph\DTO\Message;

/**
 *
 */
class FuckerFlow extends CommonBotFlow
{

    public function handleChatMessage(Message $message): void
    {
        $this->botService->telegraph()->markdown('Пошёл нахуй!')->send();
    }
}
