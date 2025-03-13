<?php

namespace Botflow\Listeners;



use Botflow\Contracts\Concerns\IRequireAuth;
use Botflow\Contracts\IBotService;
use Botflow\Exceptions\RuntimeAuthenticationErrorException;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Auth;

class ConsoleCommandFinishedListener
{
    /**
     * Create the event listener.
     */
    public function __construct(protected IBotService $botService)
    {
        //
    }

    /**
     * Handle the event.
     * @throws RuntimeAuthenticationErrorException
     */
    public function handle(CommandFinished $event): void
    {
        while ($action = $this->botService->nextAction()) {
            $requireAuthentication = in_array(IRequireAuth::class, class_implements($action::class) ?: []);
            if ($requireAuthentication && Auth::guest()) {
                throw new RuntimeAuthenticationErrorException(sprintf('Action %s requires authentication', $action::class));
            }

            $action->consoleBehavior();
        }
    }
}
