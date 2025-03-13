<?php

namespace App\Providers;

use App\Contracts\IUserRepository;
use App\Enums\Permission;
use App\Facades\Auth;
use App\Models\UserRepository;
use App\Telegraph\Models\User;
use Botflow\Exceptions\RuntimeAuthenticationErrorException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->registerEventListeners();
    }

    /**
     * @throws RuntimeAuthenticationErrorException
     */
    public function boot(): void
    {
        $this->defineGates();

        $this->app->bind(IUserRepository::class, fn () => new UserRepository());

        if (app()->runningInConsole()) {
            if ($consoleUser = env('CONSOLE_USER_ID')) {
                /** @var User $user */
                $user = User::query()->where('id', intval($consoleUser))->first();

                if (empty($user)) {
                    throw new RuntimeAuthenticationErrorException("User $consoleUser does not exist. Check CONSOLE_USER_ID .env parameter");
                }

                Auth::login($user);
            }
        }

        LogViewer::extend('telegraph', BotflowLog::class);
    }

    protected function registerEventListeners(): void
    {

    }

    protected function defineGates(): void
    {

    }
}
