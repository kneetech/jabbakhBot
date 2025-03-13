<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Telegraph\Models\User;
use Botflow\Helpers\JSON;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AddUser extends Command
{

    protected $signature = 'app:add-user {id : ID Telegram} ' .
    '{--name= : Имя пользователя} ' .
    '{--role=' . UserRole::Unknown->value . '} ' .
    '{--email=} {--password=}';

    protected $description = 'Добавить пользователя';

    /**
     * Execute the console command.
     */
    public function handle(): bool
    {
        $telegramId = $this->argument('id');

        $user = [
            'telegram_id' => $telegramId,
            'role' => $this->option('role'),
        ];

        if ($name = $this->option('name')) {
            $user['name'] = $name;
        }

        if ($email = $this->option('email')) {
            $user['email'] = $email;
        }

        if ($password = $this->option('password')) {
            $user['password'] = Hash::make($password);
        }

        $validator = Validator::make($user, [
            'telegram_id' => 'required|integer|unique:users,telegram_id',
            'name' => 'string',
            'email' => 'email:rfc,dns|unique:users,email',
            'password' => 'string',
            'role' => [Rule::enum(UserRole::class)],
        ]);

        if ($validator->fails()) {
            $this->error('Ошибка в параметрах');
            $this->warn($validator
                ->errors()
                ->toJson(JSON::CONSOLE));
            return self::FAILURE;
        }

        /** @var User $user */
        $user = User::factory()->create($user);

        $this->info('Пользователь добавлен, ID: ' . $user->id);

        return self::SUCCESS;
    }
}
