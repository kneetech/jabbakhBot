<?php

namespace App\Models;

use App\Contracts\IUserRepository;
use App\Telegraph\Models\User;

class UserRepository implements IUserRepository
{

    public function getByTelegramId(int $telegramId): ?User
    {
        /** @var User $user */
        $user = User::query()->where('telegram_id', '=', $telegramId)->first();
        return $user;
    }

    public function getByEmail(string $email): ?User
    {
        /** @var User $user */
        $user = User::query()->where('email', '=', $email)->get();
        return $user;
    }
}
