<?php

namespace Botflow\Listeners;

use Botflow\Contracts\Concerns\IRequireAuth;
use Botflow\Contracts\IBotService;
use Botflow\Events\TelegramActionTime;
use Botflow\Exceptions\Exception;
use Botflow\Exceptions\RuntimeAuthenticationErrorException;
use Botflow\Telegraph\DTO\Update;
use Illuminate\Support\Facades\Auth;

class TelegramActionTimeListener
{

    public function __construct(protected IBotService $botService, protected Update $update)
    {
        //
    }

    /**
     * @throws Exception
     */
    public function handle(TelegramActionTime $event): void
    {
        while ($action = $this->botService->nextAction()) {
            $requireAuthentication = in_array(IRequireAuth::class, class_implements($action::class) ?: []);
            if ($requireAuthentication && Auth::guest()) {
                throw new RuntimeAuthenticationErrorException(sprintf('Action %s skipped requires authentication', $action::class));
            }
            $action->telegramBehavior();
        }
    }
}
