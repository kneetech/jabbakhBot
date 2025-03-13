<?php

namespace Botflow\Contracts;

use Botflow\Telegraph\DTO\Update;
use Illuminate\Console\Concerns\InteractsWithIO;

abstract class CommonBotCommand extends CommonBotAction implements IBotCommand
{

    public bool $deleteCommandMessage = false;

    use InteractsWithIO;

    protected string $inputParams = '';

    public abstract function alias(): string;

    public abstract function helpMessage(): string;

    public function handeUpdate(Update $update): void
    {
        if ($this->deleteCommandMessage && $update->message()?->id()) {
            $this->botService->telegraph()->deleteMessage($update->message()->id())->send();
        }
    }

    public function parseInputParams(string $rawInputParamsString): void
    {
        $this->inputParams = $rawInputParamsString;
    }

    public function consoleBehavior(): void
    {
        $this->warn(sprintf('Поведение команды /%s для консоли не реализовано', $this->alias()));
    }
}
