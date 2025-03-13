<?php

namespace Botflow\Flows;

use Botflow\Contracts\CommonBotFlow;
use Botflow\Contracts\CommonBotFlowWithState;
use Botflow\Contracts\Concerns\IRequireAuth;
use Botflow\Contracts\IBotService;
use Botflow\Contracts\IFlowStateRepository;
use Botflow\Telegraph\DTO\Update;
use Botflow\Telegraph\Facades\Auth;
use Botflow\Telegraph\Models\FlowState;
use Illuminate\Support\Facades\Log;
use Botflow\Exceptions\Exception;

class FlowStateManager extends CommonBotFlow
{

    protected IFlowStateRepository $flowStateRepository;

    public function __construct(IBotService $botService, array $params = [])
    {
        parent::__construct($botService, $params);

        $this->flowStateRepository = app(IFlowStateRepository::class);
    }

    /**
     * @throws Exception
     */
    public function handleUpdate(Update $update): void
    {
        parent::handleUpdate($update);

        if (
            ($user = \App\Facades\Auth::user())
            && $user->role->isSupervisor()
        ) {
            $activeFlowStates = $this->flowStateRepository->getActiveFlowStatesForSupervisor(
                $this->botService->telegraphUser()?->id(),
                $this->botService->telegraphChat()?->chat_id
            );
        } else {
            $activeFlowStates = $this->flowStateRepository->getActiveFlowStatesForUser(
                $this->botService->telegraphUser()?->id(),
                $this->botService->telegraphChat()?->chat_id
            );
        }

        $monopolized = false;

        foreach ($activeFlowStates as $activeFlowState) {
            if ($activeFlowState->monopolizing) {
                if ($monopolized) {
                    continue;
                } else {
                    $requireAuthentication = in_array(IRequireAuth::class, class_implements($activeFlowState::class) ?: []);
                    if ($requireAuthentication && Auth::guest()) {
                        Log::warning('Trying to instantiate flow with IRequireAuth, but not logged in', [$activeFlowState->toArray(), $update->toArray()]);
                    } else {
                        $this->botService->addFlow($activeFlowState->class, ['id' => $activeFlowState->id]);
                        $monopolized = true;
                    }
                }
            } else {
                $requireAuthentication = in_array(IRequireAuth::class, class_implements($activeFlowState::class) ?: []);
                if ($requireAuthentication && Auth::guest()) {
                    Log::warning('Trying to instantiate flow with IRequireAuth, but not logged in', [$activeFlowState->toArray(), $update->toArray()]);
                } else {
                    $this->botService->addFlow($activeFlowState->class, ['id' => $activeFlowState->id]);
                }
            }
        }

        if (!$monopolized) {
            $queuedFlowStates = $this->flowStateRepository->getQueuedFlowStatesForUser(
                Auth::user()?->telegram_id,
                $this->botService->telegraphChat()?->chat_id
            );

            if (!$queuedFlowStates->isEmpty()) {
                /** @var FlowState|null $firstQueuedFlowState */
                $firstQueuedFlowState = $queuedFlowStates->first();
                if (is_null($firstQueuedFlowState)) {
                    return;
                }
                $flow = CommonBotFlowWithState::restore($firstQueuedFlowState->id);
                $requireAuthentication = in_array(IRequireAuth::class, class_implements($activeFlowState::class) ?: []);
                if ($requireAuthentication && Auth::guest()) {
                    Log::warning('Trying to activate queued flow with IRequireAuth, but not logged in', [$activeFlowState->toArray(), $update->toArray()]);
                } else {
                    $flow->activate();
                }
            }
        }
    }
}
