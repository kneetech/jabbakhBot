<?php

namespace Botflow\Contracts;

use App\Facades\Auth;
use Botflow\Exceptions\Exception;
use Botflow\Exceptions\RuntimeDataInconsistencyErrorException;
use Botflow\Exceptions\RuntimeUnexpectedErrorException;
use Botflow\Telegraph\Models\FlowState;
use Botflow\Telegraph\Models\User;
use DefStudio\Telegraph\DTO\Message;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;

abstract class CommonBotFlowWithState extends CommonBotFlow implements IBotFlowWithState
{
    protected ?FlowState $state = null;

    protected IFlowStateRepository $repository;

    /**
     * @var Message[]
     */
    protected array $messages = [];

    public function __construct(IBotService $botService, array $params = [])
    {
        parent::__construct($botService, $params);
        $stateClass = static::stateClass();

        $this->repository = app(IFlowStateRepository::class);

        if (!empty($params['id'])) {
            $this->state = $stateClass::query()->findOrFail($params['id']);

            if (empty($this->state)) {
                throw new RuntimeDataInconsistencyErrorException('Requested flow state does not exist: ' . $params['id']);
            }

            unset($params['id']);
        } else {
            $this->state = new $stateClass();
            $this->state->class = $this::class;

            if (isset($params['telegram_user_id'])) {
                $this->state->telegram_user_id = $params['telegram_user_id'];
            } elseif ($user = $this->botService->telegraphUser()) {
                $this->state->telegram_user_id = $user->id();
            }

            if (isset($params['telegram_chat_id'])) {
                $this->state->telegram_chat_id = $params['telegram_chat_id'];
            } elseif ($chat = $botService->telegraphChat()) {
                $this->state->telegram_chat_id = $chat->chat_id;
            }

            unset($params['telegram_user_id']);
            unset($params['telegram_chat_id']);
        }

        if (!is_a($this, $this->state->class)) {
            throw new RuntimeDataInconsistencyErrorException('Requested flow state belongs to other flow class: ' . $this->state->class);
        }

        $this->state->params = array_merge($this->state->params ?: [], $params);

        if (isset($params['listeners']) && is_array($params['listeners'])) {
            foreach ($params['listeners'] as $event => $listener) {
                Event::listen($event, $listener);
            }
        }

        $this->messages = Arr::map($this->state->getData('messages') ?? [], function (array $message) {
            // костыль для кривого DTO
            $message['message_id'] = $message['id'];

            if (isset($message['reply_to_message'])) {
                $message['reply_to_message']['message_id'] = $message['reply_to_message']['id'];
            }

            return Message::fromArray($message);
        });
    }

    public function state(): FlowState
    {
        return $this->state;
    }

    public function activate(): void
    {
        parent::activate();

        if ($this->state->isQueued() && $this->state->monopolizing) {
            if (!$this->repository->isStateMonopolizedForUser($this->state->telegram_user_id, $this->state->telegram_chat_id)) {
                $this->state->status = FlowStatus::ACTIVE;
                $this->store();
                $this->start();
            }
        } else {
            $this->state->status = FlowStatus::ACTIVE;
            $this->store();
            $this->start();
        }
    }

    public function start(): void
    {

    }

    /**
     * @throws RuntimeUnexpectedErrorException
     */
    public function succeed(): void
    {
        $this->state->status = FlowStatus::OK;
        $this->store();
    }

    /**
     * @throws Exception
     */
    public function cancel(?string $reason = null): void
    {
        $this->state->status = FlowStatus::CANCELLED;

        if (!empty($reason)) {
            $this->state->setData('cancellation.reason', $reason);
        }

        $this->store();
    }

    /**
     * @throws RuntimeUnexpectedErrorException
     */
    public function interrupt(int $code, ?string $reason = null): void
    {
        $this->state->status = FlowStatus::INTERRUPTED;

        $this->state->setData('interruption.code', $code);

        if (!empty($reason)) {
            $this->state->setData('interruption.reason', $reason);
        }

        $this->store();
    }

    public function handleChatMessage(Message $message): void
    {
        parent::handleChatMessage($message);

        $this->state->setData("messages.{$message->id()}", $message->toArray());

        $this->store();
    }

    /**
     * @throws RuntimeUnexpectedErrorException
     */
    public function handleEditedMessage(Message $message): void
    {
        parent::handleEditedMessage($message);

        $this->state->setData("messages.{$message->id()}", $message->toArray());

        $this->store();
    }

    public function user(): ?User
    {
        return $this->state->user()->first();
    }

    protected function id(): int
    {
        return $this->state->id;
    }

    /**
     * @throws RuntimeUnexpectedErrorException
     */
    public function store(): void
    {
        $this->state->setData('messages', Arr::map($this->messages, function (Message $message) {
            return $message->toArray();
        }));

        try {
            $this->state->saveOrFail();
        } catch (\Throwable $e) {
            throw new RuntimeUnexpectedErrorException('Flow state save failed', 0, $e);
        }
    }

    /**
     * @throws RuntimeDataInconsistencyErrorException
     */
    public static function restore(int $id): CommonBotFlowWithState
    {
        /** @var FlowState $state */
        $state = static::stateClass()::query()->findOrFail($id);

        if (empty($state)) {
            throw new RuntimeDataInconsistencyErrorException('Requested flow state does not exist');
        }

        if (!is_subclass_of($state->class, CommonBotFlowWithState::class)) {
            throw new RuntimeDataInconsistencyErrorException('Flow state record contains invalid class');
        }

        return new $state->class(app(IBotService::class), ['id' => $id]);
    }

    protected function belongsToMe(): bool
    {
        $authUser = Auth::user();
        $stateUser = $this->user();
        if (
            $authUser
            && $stateUser
            && $authUser->id === $stateUser->id
        ) {
            return true;
        }
        return false;
    }

    protected function recipientChatId(): int
    {
        if (!$this->belongsToMe()) {
            $authUser = Auth::user();
            if (
                $authUser
                && $authUser->role->isSupervisor()
            ) {
                return $authUser->telegram_id;
            }
        }
        return $this->state->telegram_user_id;
    }

    /**
     * @throws Exception
     */
    protected function setTelegraphChatByFlowClass(): void
    {
        $this->botService->setTelegraphChat($this->recipientChatId());
    }
}
