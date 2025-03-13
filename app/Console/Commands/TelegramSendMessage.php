<?php

namespace App\Console\Commands;

use Botflow\Contracts\IBotService;
use Botflow\Exceptions\Exception;
use Illuminate\Console\Command;

class TelegramSendMessage extends Command
{
    protected $signature = 'app:telegram-send-message {userid} {message}';

    protected $description = 'Отправить сообщение сотрудникам от лица бота';

    /**
     * @throws Exception
     */
    public function handle(IBotService $botService): void
    {
        $userId = $this->argument('userid');
        $message = $this->argument('message');

        $botService->setTelegraphChat($userId);
        $botService->telegraph()->markdown(escape_markdown($message))->send();
    }
}
