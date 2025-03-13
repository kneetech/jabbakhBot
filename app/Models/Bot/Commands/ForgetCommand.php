<?php

namespace App\Models\Bot\Commands;

use Botflow\Contracts\CommonBotCommand;
use Botflow\Contracts\CommonBotFlowWithState;
use Botflow\Contracts\Concerns\IRequireAuth;
use Botflow\Contracts\IFlowStateRepository;
use Botflow\Exceptions\RuntimeDataInconsistencyErrorException;
use Botflow\Exceptions\RuntimeUnexpectedErrorException;
use App\Telegraph\Models\FlowState;
use Ramsey\Collection\Collection;

class ForgetCommand extends CommonBotCommand implements IRequireAuth
{

    protected IFlowStateRepository $flowStateRepository;


    /**
     * @throws RuntimeUnexpectedErrorException
     * @throws RuntimeDataInconsistencyErrorException
     */
    protected function commonBehavior(): void
    {
        $this->flowStateRepository = app(IFlowStateRepository::class);

        /** @var Collection<FlowState> $activeFlowStates */
        $activeFlowStates = $this->flowStateRepository->getActiveFlowStatesForUser($this->telegram_user_id, $this->telegram_chat_id);

        foreach ($activeFlowStates as $activeFlowState) {
            $activeFlow = CommonBotFlowWithState::restore($activeFlowState->id);
            $activeFlow->interrupt(0, 'команда /forget');
        }

        /** @var Collection<FlowState> $queuedFlowStates */
        $queuedFlowStates = $this->flowStateRepository->getQueuedFlowStatesForUser($this->telegram_user_id, $this->telegram_chat_id);

        foreach ($queuedFlowStates as $queuedFlowState) {
            $queuedFlow = CommonBotFlowWithState::restore($queuedFlowState->id);
            $queuedFlow->interrupt(0, 'команда /forget');
        }
    }

    /**
     * @throws RuntimeUnexpectedErrorException
     * @throws RuntimeDataInconsistencyErrorException
     */
    public function consoleBehavior(): void
    {
        $this->commonBehavior();
    }

    /**
     * @throws RuntimeUnexpectedErrorException
     * @throws RuntimeDataInconsistencyErrorException
     */
    public function telegramBehavior(): void
    {
        $this->commonBehavior();
    }

    public function alias(): string
    {
        return 'forget';
    }

    public function helpMessage(): string
    {
        return 'Сбросить контекст общения с ботом';
    }
}
