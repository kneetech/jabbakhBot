<?php

namespace App\Models\Bot\Commands;

use Botflow\Contracts\CommonBotCommand;

class StartCommand extends CommonBotCommand
{


    public function telegramBehavior(): void
    {
        $this->botService->telegraph()->markdown('Привет!')->send();
    }

    public function alias(): string
    {
        return 'start';
    }

    public function helpMessage(): string
    {
        return 'Приветственное сообщение';
    }
}
