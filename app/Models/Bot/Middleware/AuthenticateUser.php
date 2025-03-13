<?php

namespace App\Models\Bot\Middleware;

use App\Enums\UserRole;
use App\Facades\UserRepositoryFacade;
use App\Models\User;
use Botflow\Contracts\CommonBotMiddleware;
use Botflow\Telegraph\DTO\Update;
use Illuminate\Support\Facades\Auth;

class AuthenticateUser extends CommonBotMiddleware
{


    public function handle(Update $update): void
    {
        $telegramUser =
            $update->message()?->from() ?:
            $update->editedMessage()?->from() ?:
            $update->channelPost()?->from() ?:
            $update->editedChannelPost()?->from() ?:
            $update->callbackQuery()?->from() ?:
            $update->inlineQuery()?->from();

        if (!$telegramUser) {
            return;
        }

        $this->botService->setTelegraphUser($telegramUser);

        if ($user = UserRepositoryFacade::getByTelegramId($telegramUser->id())) {
            Auth::login($user);
        } else {
            $user = new User([
                'name' => $telegramUser->username(),
                'telegram_id' => $telegramUser->id(),
                'role' => UserRole::Unknown
            ]);

            $user->save();

            Auth::login($user);
        }
    }
}
