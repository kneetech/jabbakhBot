<?php

namespace Botflow\Contracts;

use Botflow\Telegraph\DTO\Update;
use DefStudio\Telegraph\DTO\CallbackQuery;
use DefStudio\Telegraph\DTO\InlineQuery;
use DefStudio\Telegraph\DTO\Message;
use DefStudio\Telegraph\DTO\User;

abstract class CommonBotFlow implements IBotFlow
{

    public function __construct(protected IBotService $botService, protected array $params = [])
    {
        if (!empty($this->params['telegram_chat_id'])) {
            $this->botService->setTelegraphChat($this->params['telegram_chat_id']);
        } elseif (!empty($this->params['telegram_user_id'])) {
            $this->botService->setTelegraphChat($this->params['telegram_user_id']);
        }
    }

    public function activate(): void
    {
        //
    }

    public function handleUpdate(Update $update): void
    {
        //
    }

    public function handleCommand(string $command, string $parameter): bool
    {
        return false;
    }

    public function handleChatMessage(Message $message): void
    {
        //
    }

    public function handleEditedMessage(Message $message): void
    {
        //
    }

    public function handleChannelPost(Message $message): void
    {
        //
    }

    public function handleInlineQuery(InlineQuery $inlineQuery): void
    {
        //
    }

    public function handleCallbackQuery(CallbackQuery $callbackQuery): void
    {
        //
    }

    public function handleChatMemberJoined(User $member): void
    {
        //
    }

    public function handleChatMemberLeft(User $member): void
    {
        //
    }
}

