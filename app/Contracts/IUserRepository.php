<?php

namespace App\Contracts;

use App\Telegraph\Models\User;

interface IUserRepository
{

    public function getByTelegramId(int $telegramId): ?User;

    public function getByEmail(string $email): ?User;
}
