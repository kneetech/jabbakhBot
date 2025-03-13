<?php

namespace Botflow\Listeners;

use Botflow\Telegraph\Facades\Auth;
use Botflow\Contracts\Concerns\IRequireAuth;
use Botflow\Contracts\IBotService;
use Botflow\Events\TelegramCommandReceived;
use Botflow\Telegraph\DTO\Update;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class TelegramCommandListener
{

    public function __construct(protected IBotService $botService, protected Update $update)
    {
        //
    }

    public function handle(TelegramCommandReceived $event): void
    {

        // если команда по алиасу обработана потоками (flow),
        // то зарегистрированная команда с таким алиасом выполняться не будет
        $commandHandledByFlow = false;
        while ($flow = $this->botService->nextFlow()) {
            $commandHandledByFlow = $flow->handleCommand($event->command, $event->arguments) || $commandHandledByFlow;
            $flow->handleUpdate($this->update);
        }

        if (!$commandHandledByFlow) {
            if ($command = $this->botService->getCommand($event->command)) {
                $requireAuthentication = in_array(IRequireAuth::class, class_implements($command) ?: []);
                if ($requireAuthentication && Auth::guest()) {
                    Log::warning(
                        sprintf('Command /%s skipped, as it requires authentication', $command->alias()),
                        $this->update->message()->toArray()
                    );
                } else {
                    $ability = '/' . $command->alias();
                    if (!Gate::has($ability) || Gate::allows($ability, $event->arguments)) {
                        $command->parseInputParams($event->arguments);
                        $command->telegramBehavior();
                        $command->handeUpdate($this->update);
                    } else {
                        Log::warning(
                            sprintf('Command /%s skipped, as not allowed by gate %s', $command->alias(), $ability),
                            $this->update->message()->toArray()
                        );
                    }
                }
            } elseif ($action = $this->botService->unknownCommandAction()) {
                $action->telegramBehavior();
            }
        }
    }
}
