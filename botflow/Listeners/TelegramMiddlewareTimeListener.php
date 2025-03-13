<?php

namespace Botflow\Listeners;

use Botflow\Contracts\IBotService;
use Botflow\Contracts\IFlowStateRepository;
use Botflow\Events\TelegramMiddlewareTime;
use Botflow\Telegraph\DTO\Update;

class TelegramMiddlewareTimeListener
{

    public function __construct(
        protected IBotService $botService,
        protected Update $update,
        protected IFlowStateRepository $flowStateRepository
    )
    {
        //
    }

    public function handle(TelegramMiddlewareTime $event): void
    {
        while ($middleware = $this->botService->nextMiddleware()) {
            $middleware->handle($this->update);
        }
    }
}
