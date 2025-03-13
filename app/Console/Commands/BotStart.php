<?php

namespace App\Console\Commands;

use Botflow\Contracts\IBotService;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Console\Command;

class BotStart extends Command
{

    protected $signature = 'bot:start {--get-updates : получить обновления}';

    protected $description = 'Запуск бота: регистрация веб-хука и меню';

    public function handle(IBotService $botService): int
    {
        $appUrl = config('app.url');

        if (empty($appUrl)) {
            $this->error('Для запуска бота необходимо задать параметры .env: APP_URL');
            return self::FAILURE;
        }

        $this->info('URL: ' . $appUrl);

        if (
            ($telegramToken = $this->telegramToken())
            && ($telegramBotName = $this->telegramBotName())
        ) {
            $this->info('Имя telegram-бота: ' . $telegramBotName);
            $this->setupTelegram($botService, $telegramBotName, $telegramToken, $this->option('get-updates'));
        }

        return self::SUCCESS;
    }

    protected function telegramBotName(): ?string
    {
        return config('bot.name');
    }

    protected function telegramToken(): ?string
    {
        return config('bot.token');
    }

    protected function setupTelegram(
        IBotService $botService,
        string $botName,
        string $botToken,
        bool $getUpdates = false
    ): void
    {
        $botModel = config('telegraph.models.bot');

        $this->info('TELEGRAM | Bot token: ' . $botToken);

        /** @var TelegraphBot $bot */
        $bot = $botModel::query()->where('token', '=', $botToken)->first();

        if (empty($bot)) {
            $bot = $botModel::create([
                'token' => $botToken,
                'name' => $botName,
            ]);
            $this->info('TELEGRAM | Зарегистрирован новый бот, ID: ' . $bot->id);
        } else {
            $this->info('TELEGRAM | Найден бот, ID: ' . $bot->id);
        }

        $botService->setTelegraphBot($bot);

        $this->info('TELEGRAM | Регистрация хука...');
        $botService->telegraph()->registerWebhook(!$getUpdates)->send();

        if ($getUpdates) {
            $this->info('TELEGRAM | Запуск обработки обновлений...');
        }

        if ($menu = config('bot.menu')) {
            $this->info('TELEGRAM | Регистрация меню...');
            $bot->registerCommands($menu)->send();
        }

        $this->info('TELEGRAM | Готово');
    }
}
