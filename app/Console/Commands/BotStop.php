<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Console\Command;

class BotStop extends Command
{

    protected $signature = 'bot:stop';

    protected $description = 'Полная остановка бота: снятие веб-хука с остановкой регистрации сообщений';

    public function handle(): int
    {
        $this->handleTelegraphBot();

        return self::SUCCESS;
    }

    private function handleTelegraphBot(): void
    {
        /** @var class-string<TelegraphBot> $telegraphBotModelClass */
        $telegraphBotModelClass = config('telegraph.models.bot');
        $token = config('bot.token');
        $name = config('bot.name');

        try {
            if (
                $telegraphBotModelClass
                && $token
                && $name
            ) {
                /** @var TelegraphBot $bot */
                $bot = $telegraphBotModelClass::query()->where('token', '=', $token)->sole();
                $this->info("Бот {$bot->name}, ID: {$bot->id}");
                $this->info('Снятие хука...');
                $bot->unregisterWebhook()->send();
                $until = Carbon::now()->addDay();
                $this->info('ВНИМАНИЕ: Запуск бота без потери событий возможен до ' . $until->toDateTimeString());
                $this->info('Готово');
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
    }
}
