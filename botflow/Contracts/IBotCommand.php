<?php

namespace Botflow\Contracts;

use Botflow\Telegraph\DTO\Update as TelegramUpdate;

interface IBotCommand extends IBotAction
{


    public function alias(): string;

    /**
     * @return string markdown
     */
    public function helpMessage(): string;

    public function parseInputParams(string $rawInputParamsString): void;

    public function handeUpdate(TelegramUpdate $update): void;
}
