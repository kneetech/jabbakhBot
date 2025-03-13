<?php

namespace App\Providers;

use App\Console\Commands\BotStart;
use App\Console\Commands\BotStop;
use App\Services\BotService;
use App\Telegraph\BotflowTelegraph;
use Botflow\Console\BotCommand;
use Botflow\Contracts\IBotService;
use Botflow\Contracts\IFlowStateRepository;
use Botflow\Events\TelegramActionTime;
use Botflow\Events\TelegramCallbackQueryReceived;
use Botflow\Events\TelegramChannelPostReceived;
use Botflow\Events\TelegramCommandReceived;
use Botflow\Events\TelegramMessageReceived;
use Botflow\Events\TelegramMessageUpdateReceived;
use Botflow\Events\TelegramMiddlewareTime;
use Botflow\Listeners\ConsoleCommandFinishedListener;
use Botflow\Listeners\Subscribers\ApplicationEventsSubscriber;
use Botflow\Listeners\TelegramActionTimeListener;
use Botflow\Listeners\TelegramCallbackQueryListener;
use Botflow\Listeners\TelegramChannelPostListener;
use Botflow\Listeners\TelegramCommandListener;
use Botflow\Listeners\TelegramMessageListener;
use Botflow\Listeners\TelegramMessageUpdateListener;
use Botflow\Listeners\TelegramMiddlewareTimeListener;
use Botflow\Telegraph\DTO\Update;
use App\Telegraph\Services\FlowStateRepository;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class BotflowServiceProvider extends EventServiceProvider
{

    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        TelegramMessageReceived::class => [
            TelegramMessageListener::class,
        ],
        TelegramMessageUpdateReceived::class => [
            TelegramMessageUpdateListener::class,
        ],
        TelegramChannelPostReceived::class => [
            TelegramChannelPostListener::class,
        ],
        TelegramCommandReceived::class => [
            TelegramCommandListener::class,
        ],
        TelegramActionTime::class => [
            TelegramActionTimeListener::class,
        ],
        TelegramMiddlewareTime::class => [
            TelegramMiddlewareTimeListener::class,
        ],
        TelegramCallbackQueryReceived::class => [
            TelegramCallbackQueryListener::class,
        ],
        CommandFinished::class => [
            ConsoleCommandFinishedListener::class,
        ]
    ];

    public function register(): void
    {
        parent::register();

        $this->app->bind('telegraph', fn () => new BotflowTelegraph());
    }

    public function boot(): void
    {

        $this->app->bind(Update::class, function (Application $app) {
            $request = $app->make(Request::class);
            return Update::fromArray($request->all());
        });

        $this->app->bind(IFlowStateRepository::class, fn () => new FlowStateRepository());

        $this->app->singleton(IBotService::class, function () {
            $botService = new BotService(
                config('bot.middleware', []),
                config('bot.commands', []),
                config('bot.flows', []),
                config('bot.unknownCommandAction'),
                config('bot.flowConfigs')
            );

            if ($telegraphBot = $this->telegraphBot()) {
                $botService->setTelegraphBot($telegraphBot);
            }

            return $botService;
        });

        $this->commands([
            BotCommand::class,
            BotStart::class,
            BotStop::class,
        ]);

        Event::subscribe(ApplicationEventsSubscriber::class);
    }

    private function telegraphBot(): ?TelegraphBot
    {
        /** @var class-string<TelegraphBot> $telegraphBotModelClass */
        $telegraphBotModelClass = config('telegraph.models.bot');
        $token = env('BOT_TOKEN');
        try {
            if (
                $telegraphBotModelClass
                && $token
                && $bot = $telegraphBotModelClass::fromToken($token)
            ) {
                return $bot;
            }
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
        }
        return null;
    }

    public function shouldDiscoverEvents(): false
    {
        return false;
    }
}
