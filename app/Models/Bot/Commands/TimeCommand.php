<?php

namespace App\Models\Bot\Commands;

use Botflow\Contracts\CommonBotCommand;
use Botflow\Contracts\Concerns\IRequireAuth;

class TimeCommand extends CommonBotCommand implements IRequireAuth
{


    public function telegramBehavior(): void
    {

        $this->botService->telegraph()
            ->markdownV2(escape_markdown_v2($this->getTimeString()))
            ->send();
    }

    public function consoleBehavior(): void
    {
        $this->info($this->getTimeString());
    }

    public function alias(): string
    {
        return 'time';
    }

    public function helpMessage(): string
    {
        return 'Время';
    }

    protected function getTimeString(): string
    {
        return (now())->toDateTimeString();
    }
}
