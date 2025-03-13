<?php

namespace Botflow\Telegraph\Services;

use Botflow\Contracts\CommonBotFlowWithState;
use Botflow\Contracts\FlowStatus;
use Botflow\Contracts\IBotService;
use Botflow\Contracts\IFlowStateRepository;
use Botflow\Exceptions\Exception;
use Botflow\Telegraph\Models\FlowState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FlowStateRepository implements IFlowStateRepository
{
    /**
     * @return class-string<FlowState>
     */
    public static function stateClass(): string
    {
        return FlowState::class;
    }

    public function hasActiveState(
        ?int $userId,
        ?int $chatId,
        string $flowClass
    ): bool {
        return (bool)$this->getFlowStatesQuery($userId, $chatId, $flowClass)
            ->where('status', '=', FlowStatus::OK)
            ->orderBy('created_at')
            ->count();
    }

    public function activeState(
        ?int $userId,
        ?int $chatId,
        string $flowClass
    ) {
        try {
            /** @var FlowState $state */
            $state = $this->getFlowStatesQuery($userId, $chatId, $flowClass)
                ->where('status', '=', FlowStatus::OK)
                ->orderBy('created_at')
                ->sole();
        } catch (\Throwable $e) {
            throw new Exception($e->getMessage() . $e->getTraceAsString());
        }
        return $state;
    }

    public function createState(
        string $class,
        FlowStatus $status,
        ?int $bot_id,
        ?int $user_id,
        ?int $chat_id
    ) {
        $stateClass = static::stateClass();
        $state = new $stateClass();
        $state->class = $class;
        $state->status = $status;
        $state->telegram_user_id = $user_id;
        $state->telegram_chat_id = $chat_id;
        return $state;
    }

    protected function getFlowStatesQuery(?int $telegramUserId = null, ?int $telegramChatId = null, ?string $flowClass = null): Builder
    {
        $query = static::stateClass()::query()
            ->where(function (Builder $query) use ($telegramUserId) {
                $query->where('telegram_user_id', '=', null);

                if ($telegramUserId) {
                    $query->orWhere('telegram_user_id', '=', $telegramUserId);
                }
            })
            ->where(function (Builder $query) use ($telegramChatId) {
                $query->where('telegram_chat_id', '=', null);

                if ($telegramChatId) {
                    $query->orWhere('telegram_chat_id', '=', $telegramChatId);
                }
            });

        if (!empty($flowClass)) {
            $query->where('class', '=', $flowClass);
        }

        return $query;
    }

    public function getActiveFlowStates(string $flowClass, array $params = []): Collection
    {
        $query = static::stateClass()::query()
            ->where('class', '=', $flowClass)
            ->where('status', '=', FlowStatus::ACTIVE);

        foreach ($params as $paramKey => $paramValue) {
            $query->where("params->{$paramKey}", '=', $paramValue);
        }

        $flows = $query->orderBy('created_at', 'desc')
            ->get()
            ->all();

        return collect($flows);
    }

    /**
     * @inheritdoc
     */
    public function getActiveFlowStatesForUser(?int $userId = null, ?int $chatId = null, ?string $flowClass = null): Collection
    {
        $flows = $this->getFlowStatesQuery($userId, $chatId, $flowClass)
            ->where('status', '=', FlowStatus::ACTIVE)
            ->orderBy('created_at')
            ->get()->all();

        return collect($flows);
    }

    public function getActiveFlowStatesForSupervisor(?int $telegramUserId = null, ?int $telegramChatId = null, ?string $flowClass = null): Collection
    {
        $flows = $this->getFlowStatesQuery($telegramUserId, $telegramChatId, $flowClass)
            ->where('status', '=', FlowStatus::ACTIVE)
            ->orderBy('created_at')
            ->get()->all();

        return collect($flows);
    }

    public function getSucceedFlowStatesForUser(?int $userId = null, ?int $chatId = null, ?string $flowClass = null): Collection
    {
        $flows = $this->getFlowStatesQuery($userId, $chatId, $flowClass)
            ->where('status', '=', FlowStatus::OK)
            ->orderBy('created_at')
            ->get()->all();

        return collect($flows);
    }

    public function getSucceedOrCancelledFlowStates(?string $flowClass = null): Collection
    {
        /** @var Collection<FlowState> $flows */
        $flows = static::stateClass()::query()
            ->where('class', '=', $flowClass)
            ->whereIn('status', [FlowStatus::OK, FlowStatus::CANCELLED])
            ->get();
        return $flows;
    }

    public function getSucceedOrCancelledFlowStatesForUser(?int $userId = null, ?int $chatId = null, ?string $flowClass = null): Collection
    {
        $flows = $this->getFlowStatesQuery($userId, $chatId, $flowClass)
            ->whereIn('status', [FlowStatus::OK, FlowStatus::CANCELLED])
            ->orderBy('created_at')
            ->get()->all();

        return collect($flows);
    }

    /**
     * @inheritdoc
     */
    public function getQueuedFlowStatesForUser(?int $userId = null, ?int $chatId = null, ?string $flowClass = null): Collection
    {
        $flows = $this->getFlowStatesQuery($userId, $chatId, $flowClass)
            ->where('status', '=', FlowStatus::QUEUED)
            ->orderBy('created_at')
            ->get()->all();

        return collect($flows);
    }

    public function isStateMonopolizedForUser(?int $userId = null, ?int $chatId = null, ?string $flowClass = null): bool
    {
        $query = $this->getFlowStatesQuery($userId, $chatId, $flowClass)
            ->where('status', '=', FlowStatus::ACTIVE)
            ->where('monopolizing', '=', true);

        return $query->exists();
    }

    public function restoreFlow(int $stateId): ?CommonBotFlowWithState
    {
        /** @var FlowState $state */
        $state = static::stateClass()::query()->findOrFail($stateId);

        if (empty($state)) {
            return null;
            //throw new RuntimeDataInconsistencyErrorException('Requested flow state does not exist');
        }

        if (!is_subclass_of($state->class, CommonBotFlowWithState::class)) {
            return null;
            //throw new RuntimeDataInconsistencyErrorException('Flow state record contains invalid class');
        }

        return new $state->class(app(IBotService::class), ['id' => $stateId]);
    }

    public function getLastFlowForUser(?int $userId = null, ?int $chatId = null, ?string $flowClass = null): ?CommonBotFlowWithState
    {
        /** @var FlowState|null $flowState */
        $flowState = $this->getFlowStatesQuery($userId, $chatId, $flowClass)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!empty($flowState)) {
            return $this->restoreFlow($flowState->id);
        }

        return null;
    }

    public function soleActiveFlow(string $flowClass): ?CommonBotFlowWithState
    {
        $state = $this->soleActiveState($flowClass);
        if (!$state) {
            return null;
        }
        return $this->restoreFlow($state->id);
    }

    public function soleActiveState(string $flowClass): ?FlowState
    {
        return $this->soleState(FlowStatus::ACTIVE, $flowClass);
    }

    public function soleState(FlowStatus $status, string $flowClass): ?FlowState
    {
        /** @var FlowState|null $state */
        $state = static::stateClass()::query()
            ->where('class', '=', $flowClass)
            ->where('status', '=', $status)
            ->first();
        return $state;
    }
}
