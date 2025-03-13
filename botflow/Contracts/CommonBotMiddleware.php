<?php

namespace Botflow\Contracts;

use Botflow\Telegraph\DTO\Update as TelegramUpdate;

abstract class CommonBotMiddleware implements IBotMiddleware
{

    public function __construct(protected IBotService $botService, protected array $params = [])
    {
        //
    }

    public abstract function handle(TelegramUpdate $update): void;
}
