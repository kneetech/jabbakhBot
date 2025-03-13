<?php

namespace Botflow\Listeners\Subscribers;

use Botflow\Contracts\IBotFlowWithState;
use Botflow\Contracts\IFlowStateRepository;
use Botflow\Exceptions\RuntimeConfigurationErrorException;
use Botflow\Telegraph\Models\FlowState;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ApplicationEventsSubscriber implements ShouldQueue
{

    protected array $flowStateEvents;

    /**
     * @param IFlowStateRepository $flowStateRepository
     *
     * @throws RuntimeConfigurationErrorException
     */
    public function __construct(private readonly IFlowStateRepository $flowStateRepository)
    {
        $flowStateEvents = config('bot.flowStateEvents', []);

        if (!is_array($flowStateEvents)) {
            throw new RuntimeConfigurationErrorException('Bot.events config section must be an array');
        }

        foreach ($flowStateEvents as $eventClass => $listeners) {
            if (!is_array($listeners)) {
                throw new RuntimeConfigurationErrorException('Bot.events.{EventClass} is a listeners section, it must be an array');
            }

            foreach ($listeners as $listenerClass => $listenerMethod) {
                if (!is_subclass_of($listenerClass, IBotFlowWithState::class)) {
                    throw new RuntimeConfigurationErrorException('Only classes supported IBotFlowWithState allowed as listeners (keys) in a bot.events.{EventClass} config node');
                }

                if (!is_string($listenerMethod)) {
                    throw new RuntimeConfigurationErrorException('Bot.events.{EventClass}.{ListenerClass} config node value must be a string (name of method)');
                }
            }

        }

        $this->flowStateEvents = $flowStateEvents;
    }

    /**
     * Register the listeners for the subscriber
     */
    public function subscribe(): array
    {
        $result = [];

        foreach (array_keys($this->flowStateEvents) as $eventClass) {
            $result[$eventClass] = 'handleApplicationEvent';
        }

        return $result;
    }

    public function handleApplicationEvent($event): void
    {
        $listenerFlowClasses = $this->flowStateEvents[$event::class] ?? [];

        foreach ($listenerFlowClasses as $listenerFlowClass => $listenerMethod) {
            /** @var Collection<FlowState> $listenerFlowStates */
            $listenerFlowStates = $this->flowStateRepository->getActiveFlowStates($listenerFlowClass);

            foreach ($listenerFlowStates as $listenerFlowState) {
                $flow = $this->flowStateRepository->restoreFlow($listenerFlowState->id);

                try {
                    call_user_func([$flow, $listenerMethod], $event);
                } catch (\BadMethodCallException $e) {
                    Log::error('Class ' . $listenerFlowClass . ' has no event listener method ' . $listenerMethod);
                }
            }

        }
    }
}
