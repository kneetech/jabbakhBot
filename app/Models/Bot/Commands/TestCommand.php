<?php

namespace App\Models\Bot\Commands;

use Botflow\Contracts\CommonBotCommand;

class TestCommand extends CommonBotCommand
{
    private const MESSAGE = 'Тестовая штука-дрюка';
    private const COMMAND = 'test';

    public function consoleBehavior(): void
    {
        $this->info(self::MESSAGE);
    }

    public function telegramBehavior(): void
    {
        $this->botService->telegraph()->message(self::MESSAGE)->send();
    }

    public function alias(): string
    {
        return self::COMMAND;
    }

    public function helpMessage(): string
    {
        return self::MESSAGE;
    }
}
