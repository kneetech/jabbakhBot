<?php

namespace Botflow\Telegraph\DTO;

use DefStudio\Telegraph\DTO\CallbackQuery;
use DefStudio\Telegraph\DTO\InlineQuery;
use DefStudio\Telegraph\DTO\Message;
use Illuminate\Contracts\Support\Arrayable;

class Update implements Arrayable
{

    private ?int $id = null;

    private ?Message $message = null;

    private ?Message $editedMessage = null;

    private ?Message $channelPost = null;

    private ?Message $editedChannelPost = null;

    private ?InlineQuery $inlineQuery = null;

    private ?CallbackQuery $callbackQuery = null;


    public static function fromArray(array $data): Update
    {
        $update = new self();

        $update->id = $data['update_id'] ?? null;

        if (isset($data['message'])) {
            $update->message = Message::fromArray($data['message']);
        }

        if (isset($data['edited_message'])) {
            $update->editedMessage = Message::fromArray($data['edited_message']);
        }

        if (isset($data['channel_post'])) {
            $update->channelPost = Message::fromArray($data['channel_post']);
        }

        if (isset($data['edited_channel_post'])) {
            $update->editedChannelPost = Message::fromArray($data['edited_channel_post']);
        }

        if (isset($data['inline_query'])) {
            $update->inlineQuery = InlineQuery::fromArray($data['inline_query']);
        }

        if (isset($data['callback_query'])) {
            $update->callbackQuery = CallbackQuery::fromArray($data['callback_query']);
        }

        return $update;
    }

    public function toArray()
    {
        return [
            'update_id'           => $this->id,
            'message'             => $this->message?->toArray(),
            'edited_message'      => $this->editedMessage?->toArray(),
            'channel_post'        => $this->channelPost?->toArray(),
            'edited_channel_post' => $this->editedChannelPost?->toArray(),
            'inline_query'        => $this->inlineQuery?->toArray(),
            'callback_query'      => $this->callbackQuery?->toArray(),
        ];
    }

    public function id(): int
    {
        return $this->id();
    }

    public function message(): ?Message
    {
        return $this->message;
    }

    public function editedMessage(): ?Message
    {
        return $this->editedMessage;
    }

    public function channelPost(): ?Message
    {
        return $this->channelPost;
    }

    public function editedChannelPost(): ?Message
    {
        return $this->editedChannelPost;
    }

    public function callbackQuery(): ?CallbackQuery
    {
        return $this->callbackQuery;
    }

    public function inlineQuery(): ?InlineQuery
    {
        return $this->inlineQuery;
    }
}
