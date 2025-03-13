<?php

namespace App\Models\Bot\Commands;

use App\Models\Bot\Actions\SayHelloAction;
use Botflow\Contracts\CommonBotCommand;

class HelloCommand extends CommonBotCommand
{

    public function alias(): string
    {
        return 'hello';
    }

    public function helpMessage(): string
    {
        return 'Команда приветствия';
    }

    public function telegramBehavior(): void
    {
        $this->botService->addAction(SayHelloAction::class);
    }

    public function consoleBehavior(): void
    {
        $this->botService->addAction(SayHelloAction::class);
    }
}
