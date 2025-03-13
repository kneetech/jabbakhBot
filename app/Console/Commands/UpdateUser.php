<?php

namespace App\Console\Commands;

use App\Contracts\IUserRepository;
use App\Enums\UserRole;
use Botflow\Helpers\JSON;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateUser extends Command
{

    protected $signature = 'app:update-user {id : ID Telegram} ' .
                           '{--email=} {--password=} {--name=} {--role=}';

    protected $description = 'Изменение свойств пользователя';

    public function handle(IUserRepository $userRepository): int
    {
        $telegramId = $this->argument('id');

        $user = $userRepository->getByTelegramId($telegramId);

        if (empty($user)) {
            $this->error('Пользователь не найден');
            return self::FAILURE;
        }

        $validator = Validator::make($this->options(), [
            'email' => 'nullable|email:rfc,dns|unique:users,email',
            'password' => 'nullable|string',
            'name' => 'nullable|string',
            'role' => ['nullable', Rule::enum(UserRole::class)],
        ]);

        if ($validator->fails()) {
            $this->error('Ошибка в параметрах');
            $this->warn($validator
                ->errors()
                ->toJson(JSON::CONSOLE));
            return self::FAILURE;
        }

        $userPatch = array_filter($validator->safe()->all());
        $user->fill($userPatch);
        $user->save();

        $this->info('Пользователь изменён, ID: ' . $user->id);
        $this->info(json_encode($userPatch, JSON::CONSOLE));

        return self::SUCCESS;
    }
}
