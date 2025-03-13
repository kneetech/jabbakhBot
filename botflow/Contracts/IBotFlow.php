<?php

namespace Botflow\Contracts;

use Botflow\Exceptions\Exception;
use Botflow\Telegraph\DTO\Update;
use DefStudio\Telegraph\DTO\CallbackQuery;
use DefStudio\Telegraph\DTO\InlineQuery;
use DefStudio\Telegraph\DTO\Message;
use DefStudio\Telegraph\DTO\User;

interface IBotFlow
{
    /**
     * Params can contain `id` attribute -- if so then flow will be restored from FlowState model (eloquent)
     * Other params will be passed to `params` attribute of state model instance
     *
     * @throws Exception
     */
    public function __construct(IBotService $botService, array $params = []);

    /**
     * @throws Exception
     */
    public function activate(): void;

    /**
     * @param string $command
     * @param string $parameter
     * @return bool флаг, говорящий о том что команда обработана потоком
     */
    public function handleCommand(string $command, string $parameter): bool;

    public function handleUpdate(Update $update): void;

    /**
     * @throws Exception
     */
    public function handleChatMessage(Message $message): void;

    public function handleEditedMessage(Message $message): void;

    public function handleChannelPost(Message $message): void;

    public function handleInlineQuery(InlineQuery $inlineQuery): void;

    public function handleCallbackQuery(CallbackQuery $callbackQuery): void;

    public function handleChatMemberJoined(User $member): void;

    public function handleChatMemberLeft(User $member): void;
}
