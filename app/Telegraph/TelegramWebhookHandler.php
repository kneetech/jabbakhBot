<?php

namespace App\Telegraph;

use Botflow\Contracts\FlowBed;
use Botflow\Contracts\IBotService;
use Botflow\Events\TelegramActionTime;
use Botflow\Events\TelegramCallbackQueryReceived;
use Botflow\Events\TelegramChannelPostReceived;
use Botflow\Events\TelegramCommandReceived;
use Botflow\Events\TelegramMessageReceived;
use Botflow\Events\TelegramMessageUpdateReceived;
use Botflow\Events\TelegramMiddlewareTime;
use Botflow\Traits\HasJsonArrayWithoutLineBreaks;
use DefStudio\Telegraph\DTO\CallbackQuery;
use DefStudio\Telegraph\DTO\Message;
use DefStudio\Telegraph\Exceptions\TelegramWebhookException;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

/**
 * Кастомный WebhookHandler, внедряющий логику Botflow
 */
class TelegramWebhookHandler extends WebhookHandler
{
    use HasJsonArrayWithoutLineBreaks;

    protected IBotService $botService;

    protected ?string $command = null;

    protected ?string $commandParams = null;

    public function __construct()
    {
        parent::__construct();

        $this->botService = app(IBotService::class);
    }

    /**
     * @throws \Throwable
     */
    public function handle(Request $request, TelegraphBot $bot): void
    {

        $this->bot = $bot;
        $this->botService->setTelegraphBot($bot);

        $this->request = $request;

        $flowBed = $this->processRequest();

        TelegramMiddlewareTime::dispatch();

        $this->logRequest($flowBed);

        switch ($flowBed) {
            case FlowBed::COMMAND:
                TelegramCommandReceived::dispatch($this->command, $this->commandParams);
                break;
            case FlowBed::MESSAGE:
                TelegramMessageReceived::dispatch($this->message);
                break;
            case FlowBed::INLINE_QUERY:
                break;
            case FlowBed::CALLBACK_QUERY:
                TelegramCallbackQueryReceived::dispatch($this->callbackQuery);
                break;
            case FlowBed::MESSAGE_UPDATE:
                TelegramMessageUpdateReceived::dispatch($this->message);
                break;
            case FlowBed::CHANNEL_POST:
                TelegramChannelPostReceived::dispatch($this->message);
                break;
            case FlowBed::CHANNEL_POST_UPDATE:
                // @todo: implement channel post update flow bed support
                break;
            case FlowBed::CHAT_MEMBERS_JOINED:
                // @todo: implement chat members joined flow bed support
                break;
            case FlowBed::CHAT_MEMBERS_LEFT:
                // @todo: implement chat members left flow bed support
                break;
        }

        TelegramActionTime::dispatch();
    }

    /**
     * @throws \Throwable
     */
    protected function processRequest(): ?FlowBed
    {
        try {
            if ($this->request->has('message')) {
                $this->message = Message::fromArray($this->request->input('message'));
                $this->extractMessageData();

                if (!empty($this->command)) {
                    return FlowBed::COMMAND;
                }

                if ($this->message?->newChatMembers()->isNotEmpty()) {
                    return FlowBed::CHAT_MEMBERS_JOINED;
                }

                if ($this->message?->leftChatMember() !== null) {
                    return FlowBed::CHAT_MEMBERS_LEFT;
                }

                return FlowBed::MESSAGE;
            }

            if ($this->request->has('edited_message')) {
                $this->message = Message::fromArray($this->request->input('edited_message'));
                $this->extractMessageData();
                return FlowBed::MESSAGE_UPDATE;
            }

            if ($this->request->has('channel_post')) {
                $this->message = Message::fromArray($this->request->input('channel_post'));
                $this->extractMessageData();
                return FlowBed::CHANNEL_POST;
            }

            if ($this->request->has('callback_query')) {
                $this->callbackQuery = CallbackQuery::fromArray($this->request->input('callback_query'));
                $this->extractCallbackQueryData();

                if (!empty($this->command)) {
                    return FlowBed::COMMAND;
                }

                return FlowBed::CALLBACK_QUERY;
            }

            if ($this->request->has('inline_query')) {
                // @todo: extract inline query data
                return FlowBed::INLINE_QUERY;
            }
        } catch (\Throwable $throwable) {
            $this->onFailure($throwable);
        }

        Log::warning('Unhandled flow bed');
        return null;
    }

    protected function canHandle(string $action): bool
    {
        if (method_exists($this, $action)) {
            return false;
        }

        return true;
    }

    public function __call(string $name, array $arguments)
    {
        TelegramCommandReceived::dispatch($name, !empty($arguments[0]) && is_string($arguments[0]) ? $arguments[0] : '');
    }

    public function handleChatMessage(Stringable $text): void
    {
        TelegramMessageReceived::dispatch($this->message);
    }

    protected function setupChat(): void
    {
        parent::setupChat();

        $this->botService->setTelegraphChat($this->chat);
    }

    protected function extractMessageData(): void
    {
        parent::extractMessageData();

        $text = Str::of($this->message?->text() ?? '');

        if ($text->startsWith('/')) {
            $this->command       = (string)$text->after('/')->before(' ')->before('@');
            $this->commandParams = (string)$text->after('@')->after(' ');
        } else {
            $this->command       = null;
            $this->commandParams = null;
        }
    }

    /**
     * @throws TelegramWebhookException
     */
    protected function extractCallbackQueryData(): void
    {
        parent::extractCallbackQueryData();

        $this->command       = $this->callbackQuery?->data()->get('action') ?? '';
        $this->commandParams = $this->callbackQuery?->data()->get('params') ?? '';
    }

    protected function logRequest(?FlowBed $flowBed): void
    {
        Log::channel('telegraph')->info(
            '[HOOK]',
            $this->jsonArrayWithoutLineBreaks($this->request->toArray()),
        );
    }
}
