<?php

namespace App\Facades;

use App\Contracts\IUserRepository;
use App\Telegraph\Models\User;
use Illuminate\Support\Facades\Facade;

/**
 * @method static User|null getByTelegramId(int $telegramId)
 * @method static User|null getByEmail(string $email)
 */
class UserRepositoryFacade extends Facade
{

    protected static function getFacadeAccessor(): string
    {
        return IUserRepository::class;
    }
}
