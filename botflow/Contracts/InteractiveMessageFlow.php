<?php

namespace Botflow\Contracts;

use Botflow\Exceptions\Exception;
use Botflow\Exceptions\RuntimeConfigurationErrorException;
use Botflow\Exceptions\RuntimeUnexpectedErrorException;
use DefStudio\Telegraph\DTO\CallbackQuery;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;

abstract class InteractiveMessageFlow extends CommonBotFlowWithState
{


    public abstract function picture(): ?string;

    public abstract function message(): string;

    /**
     * @return InteractiveMessageButton[][]
     * @throws Exception
     */
    public abstract function buttons(): array;

    public abstract function outro(): ?string;

    public abstract function callback($params): void;

    /**
     * @throws Exception
     */
    public function handleCallbackQuery(CallbackQuery $callbackQuery): void
    {
        $stateId = $callbackQuery->data()->get('state-id');

        if (!empty($stateId) && $stateId == $this->state->id) {
            $method = $callbackQuery->data()->get('method');

            if ($method && method_exists($this, $method)) {
                $this->$method($callbackQuery->data());
            } else {
                $this->callback($callbackQuery->data());
            }

            if ($this->state->isActive()) {
                $this->update();
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function keyboard(): Keyboard
    {
        $keyboard = Keyboard::make();

        foreach ($this->buttons() as $buttonsRow) {
            $row = [];

            foreach ($buttonsRow as $button) {
                if (!is_a($button, InteractiveMessageButton::class)) {
                    throw new RuntimeConfigurationErrorException('Bad buttons configuration for interactive message flow');
                }

                $params = $button->params() ?? [];
                $params['state-id'] = $this->state->id;
                $command = $button->command();
                $url = $button->url();

                $button = Button::make($button->title());
                if ($url) {
                    $button->url($url);
                } else {
                    foreach ($params as $key => $value) {
                        $button = $button->param($key, $value);
                    }

                    if ($command) {
                        $button->action($command);
                    }
                }

                $row[] = $button;
            }
            $keyboard = $keyboard->row($row);
        }

        return $keyboard;
    }

    /**
     * @throws Exception
     */
    public function update(): void
    {
        if ($messageId = $this->state->getData('message-id')) {
            $this->setTelegraphChatByFlowClass();

            $this->botService->telegraph()
                ->markdownV2($this->message())
                ->editCaption($messageId)
                ->keyboard($this->keyboard())
                ->send();
        }
    }

    /**
     * @throws Exception
     */
    protected function sendInteractiveMessage(): int
    {
        $this->setTelegraphChatByFlowClass();

        $telegraph = $this->botService->telegraph()
            ->markdownV2($this->message())
            ->keyboard($this->keyboard());

        if ($picture = $this->picture()) {
            $telegraph = $telegraph->photo($picture);
        }

        $response = $telegraph->send();

        return $response->telegraphMessageId();
    }

    /**
     * @throws Exception
     */
    public function start(): void
    {
        parent::start();

        $interactiveMessageId = $this->sendInteractiveMessage();

        if ($oldMessageId = $this->state->getData('message-id')) {
            $this->botService->telegraph()->deleteMessage($oldMessageId)->send();
        }

        $this->state->setData('message-id', $interactiveMessageId);

        $this->store();
    }

    public function succeed(): void
    {
        parent::succeed();

        $this->finalize();
    }

    /**
     * @throws Exception
     */
    public function interrupt(int $code, ?string $reason = null): void
    {
        parent::interrupt($code, $reason);

        $this->finalize();
    }

    public function cancel(?string $reason = null): void
    {
        parent::cancel($reason);

        $this->finalize();
    }

    /**
     * @throws Exception
     */
    protected function finalize(): void
    {
        if ($messageId = $this->state->getData('message-id')) {
            if ($outro = $this->outro()) {
                $this->setTelegraphChatByFlowClass();

                $this->botService->telegraph()
                    ->deleteKeyboard($messageId)
                    ->send();

                $this->botService->telegraph()
                    ->editCaption($messageId)
                    ->markdownV2($outro)
                    ->send();
            } else {
                $this->botService->telegraph()
                    ->deleteMessage($messageId)
                    ->send();
            }
        }

        $this->state()->setData('finalizedAt', now());
        $this->store();
    }
}
