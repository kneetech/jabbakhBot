<?php

namespace App\Telegraph;

use App\Telegraph\Concerns\ComposesMessagesExtension;
use App\Telegraph\Concerns\SetsReactions;
use Botflow\Exceptions\Exception;
use Botflow\Traits\HasJsonArrayWithoutLineBreaks;
use DefStudio\Telegraph\Client\TelegraphResponse;
use DefStudio\Telegraph\Concerns\ComposesMessages;
use DefStudio\Telegraph\Concerns\ManagesKeyboards;
use DefStudio\Telegraph\Concerns\SendsAttachments;
use DefStudio\Telegraph\Exceptions\TelegramWebhookException;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Telegraph;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Кастомный телеграф, с блэкджеком
 *
 * @see ComposesMessages
 * @method BotflowTelegraph message(string $message)
 * @method BotflowTelegraph markdownV2(string $message = null)
 * @method BotflowTelegraph markdown(string $message = null)
 * @method BotflowTelegraph edit(int $messageId)
 * @method BotflowTelegraph editKeyboard(int $messageId)
 *
 * @see ManagesKeyboards
 * @method BotflowTelegraph keyboard(callable|array|Keyboard $keyboard)
 * @method BotflowTelegraph removeReplyKeyboard(bool $selective = false)
 *
 * @see SendsAttachments
 * @method BotflowTelegraph editCaption(int $messageId)
 * @method BotflowTelegraph sticker(string $path, string $filename = null)
 */
class BotflowTelegraph extends Telegraph
{
    use HasJsonArrayWithoutLineBreaks;

    public const ENDPOINT_SET_MESSAGE_REACTION = 'setMessageReaction';
    public const ENDPOINT_EDIT_MESSAGE_KEYBOARD = 'editMessageReplyMarkup';

    use SetsReactions;
    use ComposesMessagesExtension;

    public function __construct()
    {
        parent::__construct();
    }

    public function clear(): self
    {
        $telegraph = clone $this;

        $telegraph->files = Collection::empty();
        $telegraph->data = [];

        return $telegraph;
    }

    /**
     * @throws Exception
     */
    public function send(): TelegraphResponse
    {
        try {
            $response = TelegraphResponse::fromResponse($this->sendRequestToTelegram());
        } catch (\Throwable $e) {
            throw new Exception($e->getMessage());
        }

        $this->logRequestAndResponse($response);

        return $response;
    }

    protected function getWebhookUrl(): string
    {
        $customWebhookUrl = config('telegraph.webhook.domain', config('telegraph.custom_webhook_domain'));

        if ($customWebhookUrl === null) {
            $url = route('telegraph.webhook', $this->getBot());

            if (!str_starts_with($url, 'https://')) {
                throw TelegramWebhookException::invalidScheme();
            }

            return $url;
        }

        return $customWebhookUrl . route('telegraph.webhook', $this->getBot(), false);
    }

    /**
     * @throws TelegramWebhookException
     */
    public function registerWebhook(?bool $dropPendingUpdates = false, ?int $maxConnections = null, ?string $secretToken = null, ?array $allowedUpdates = null): Telegraph
    {
        $telegraph = clone $this;

        $telegraph->endpoint = self::ENDPOINT_SET_WEBHOOK;
        $telegraph->data = [
            'url' => $this->getWebhookUrl(),
            'drop_pending_updates' => $dropPendingUpdates,
        ];

        return $telegraph;
    }

    public function withoutPreview(): self
    {
        $telegraph = clone $this;

        $telegraph->data['link_preview_options'] = [
            'is_disabled' => true
        ];

        return $telegraph;
    }

    public function getChatInfoByUsername(string $username): self
    {
        $telegraph = clone $this;

        $telegraph->endpoint = self::ENDPOINT_GET_CHAT_INFO;
        $telegraph->data['chat_id'] = $username;

        return $telegraph;
    }

    public function getAllData(): array
    {
        return $this->data;
    }

    protected function logRequestAndResponse(TelegraphResponse $response): void
    {
        $data = $this->toArray();

        Log::channel('telegraph')->info(
            '[SEND]',
            $this->jsonArrayWithoutLineBreaks([
                'url'      => $data['url'],
                'payload'  => $data['payload'],
                'response' => [
                    'headers' => $response->headers(),
                    'body'    => array_filter($response->json(), function ($key, $value) {
                        return $key != 'files';
                    }, ARRAY_FILTER_USE_BOTH)
                ],
            ]),
        );
    }
}
