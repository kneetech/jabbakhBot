<?php

namespace App\Models\Bot\Actions;

use App\Enums\UserRole;
use App\Facades\Auth;
use Botflow\Contracts\CommonBotAction;
use Botflow\Exceptions\Exception;
use DefStudio\Telegraph\Enums\ChatActions;
use DefStudio\Telegraph\Exceptions\TelegraphException;

class SayHelloAction extends CommonBotAction
{

    protected string $message;

    protected function boot(): void
    {
        if (Auth::guest()) {
            $this->message = 'Привет!';
        } else {
            $user = Auth::user();
            switch ($user->role) {
                case UserRole::Root:
                    $this->message = "Приветствую тебя, {$user->name}!";
                    break;
                case UserRole::Admin:
                case UserRole::Employee:
                    $this->message = "Здравствуйте, {$user->name}!";
                    break;
                case UserRole::Unknown:
                    $this->message = "Привет, {$user->name}!";
                    break;
            }
        }
    }

    public function consoleBehavior(): void
    {
        $this->info($this->message);
    }

    public function telegramBehavior(): void
    {
        try {
            $telegraph = $this->botService->telegraph()->chatAction(ChatActions::TYPING);
        } catch (TelegraphException $e) {
            throw new Exception($e->getMessage());
        }

        $telegraph->send();

        if ($this->botService->telegraphChat()) {
            $this->botService->telegraphChat()
                ->markdown(escape_markdown($this->message))
                ->dispatch()->delay(1);
        }
    }
}
