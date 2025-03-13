<?php

namespace Botflow\Contracts;

use Botflow\Telegraph\Facades\Auth;
use Illuminate\Console\Concerns\InteractsWithIO;
use Symfony\Component\Console\Output\ConsoleOutput;

abstract class CommonBotAction implements IBotAction
{
    use InteractsWithIO;

    protected string $inputParams = '';

    protected ?int $telegram_user_id = null;

    protected ?int $telegram_chat_id = null;


    public function __construct(protected IBotService $botService, protected array $params = [])
    {
        $this->output = new ConsoleOutput();


        if (isset($this->params['telegram_user_id'])) {
            $this->telegram_user_id = $this->params['telegram_user_id'];
        } elseif ($chat = $this->botService->telegraphChat()) {
            $this->telegram_user_id = $chat->chat_id;
        } elseif ($user = Auth::user()) {
            $this->telegram_user_id = $user->telegram_id;
        }

        if (isset($this->params['telegram_chat_id'])) {
            $this->telegram_chat_id = $this->params['telegram_chat_id'];
        } else {
            $this->telegram_chat_id = $this->telegram_user_id;
        }

        $this->boot();
    }

    protected function boot(): void
    {
    }

    public function consoleBehavior(): void
    {
        $this->warn(sprintf('Поведение %s для консоли не реализовано', static::class));
    }

    public function telegramBehavior(): void
    {
        //
    }
}
