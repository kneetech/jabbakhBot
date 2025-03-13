<?php

namespace Botflow\Console;

use Botflow\Contracts\Concerns\IRequireAuth;
use Botflow\Contracts\IBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class BotCommand extends Command
{
    protected $signature = 'bot:command {cmd} {params?} {--about : справка по команде}';

    protected $description = 'Команда бота';

    public function handle(IBotService $botService): void
    {
        $commandAlias = $this->argument('cmd');
        $params = $this->argument('params') ?: '';

        $command = $botService->getCommand($commandAlias);

        if (empty($command)) {
            if ($action = $botService->unknownCommandAction()) {
               $action->consoleBehavior();
            } else {
                $this->warn("Команда /{$commandAlias} не зарегистрирована");
            }
        } else {
            if ($this->option('about')) {
                $this->info($command->helpMessage());
            } else {
                $requireAuthentication = in_array(IRequireAuth::class, class_implements($command::class) ?: []);
                if ($requireAuthentication && Auth::guest()) {
                    $this->warn(sprintf('Команда /%s требует аутентификации пользователя', $command->alias()));
                } else {
                    $ability = '/' . $command->alias();
                    if (!Gate::has($ability) || Gate::allows($ability, $params)) {
                        $command->parseInputParams($params);
                        $command->consoleBehavior();
                    } else {
                        $this->warn(sprintf('Command /%s skipped, as not allowed by gate %s', $command->alias(), $ability));
                    }
                }
            }
        }
    }
}
