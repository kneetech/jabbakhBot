<?php

namespace Botflow\Contracts;

use Botflow\Exceptions\Exception;
use Botflow\Telegraph\Models\FlowState;
use Illuminate\Support\Collection;

interface IFlowStateRepository
{
    /**
     * @return class-string
     */
    public static function stateClass(): string;

    public function hasActiveState(
        ?int $userId,
        ?int $chatId,
        string $flowClass
    ): bool;

    /**
     * @throws Exception
     */
    public function activeState(
        ?int $userId,
        ?int $chatId,
        string $flowClass
    );

    /**
     * @throws Exception
     */
    public function createState(
        string $class,
        FlowStatus $status,
        ?int $bot_id,
        ?int $user_id,
        ?int $chat_id
    );

    public function getActiveFlowStates(string $flowClass, array $params = []): Collection;

    public function getActiveFlowStatesForUser(?int $userId = null, ?int $chatId = null, ?string $flowClass = null): Collection;

    public function getActiveFlowStatesForSupervisor(?int $telegramUserId = null, ?int $telegramChatId = null, ?string $flowClass = null): Collection;

    public function getSucceedFlowStatesForUser(?int $userId = null, ?int $chatId = null, ?string $flowClass = null): Collection;

    public function getSucceedOrCancelledFlowStates(?string $flowClass = null): Collection;

    public function getSucceedOrCancelledFlowStatesForUser(?int $userId = null, ?int $chatId = null, ?string $flowClass = null): Collection;

    public function getQueuedFlowStatesForUser(?int $userId = null, ?int $chatId = null, ?string $flowClass = null): Collection;

    public function isStateMonopolizedForUser(?int $userId = null, ?int $chatId = null, ?string $flowClass = null): bool;

    public function restoreFlow(int $stateId): ?CommonBotFlowWithState;

    public function getLastFlowForUser(?int $userId = null, ?int $chatId = null, ?string $flowClass = null): ?CommonBotFlowWithState;

    public function soleActiveFlow(string $flowClass): ?CommonBotFlowWithState;

    public function soleActiveState(string $flowClass): ?FlowState;

    public function soleState(FlowStatus $status, string $flowClass): ?FlowState;
}
