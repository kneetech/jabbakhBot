<?php

namespace App\Models\Bot\Actions;

use Botflow\Contracts\CommonBotAction;

class UnknownCommandAction extends CommonBotAction
{


    public function telegramBehavior(): void
    {
        $this->botService->setTelegraphChat($this->telegram_user_id);
        $this->botService->telegraph()
            ->markdownV2(escape_markdown_v2('Я такой команды не знаю. Может быть команда /help поможет?'))
            ->send();
    }
}
