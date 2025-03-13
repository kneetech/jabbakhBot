<?php

namespace Botflow\Contracts;

use Botflow\Telegraph\DTO\Update as TelegramUpdate;

interface IBotMiddleware
{

    public function __construct(IBotService $botService, array $params = []);

    public function handle(TelegramUpdate $update): void;
}
