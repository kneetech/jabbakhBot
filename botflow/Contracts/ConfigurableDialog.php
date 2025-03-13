<?php

namespace Botflow\Contracts;


use Botflow\Telegraph\Models\FlowState;
use Botflow\Exceptions\Exception;
use DefStudio\Telegraph\DTO\CallbackQuery;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;

class ConfigurableDialog extends CommonDialog
{
    protected ?string $introText = null;

    protected ?string $outroText = null;

    protected ?string $dispatchResultsWith = null;

    /** @var ConfigurableDialogQuestion[] */
    protected array $questions;

    protected int $currentQuestionNumber = 0;

    public function __construct(IBotService $botService, array $params = [])
    {
        parent::__construct($botService, $params);

        // валидация params выполняется при чтении конфига диалога в BotService

        $this->introText = $this->state->params['intro'] ?? null;
        $this->outroText = $this->state->params['outro'] ?? null;

        if (isset($this->state->params['editAnswers'])) {
            $this->editAnswers = $this->state->params['editAnswers'];
        }

        if (isset($this->state->params['explicitAccept'])) {
            $this->explicitAccept = $this->state->params['explicitAccept'];
        }

        if (isset($this->state->params['explicitAcceptReply'])) {
            $this->explicitAcceptReply = $this->state->params['explicitAcceptReply'];
        }

        $this->dispatchResultsWith = $this->state->params['dispatchResultsWith'] ?? null;

        if (isset($this->state->params['questions'])) {
            foreach ($this->state->params['questions'] as $questionConfig) {
                $questionClass = $questionConfig['class'] ?? ConfigurableDialogQuestion::class;
                $this->questions[] = new $questionClass($this, $this->botService, $questionConfig);
            }
        }

        $this->currentQuestionNumber = $this->state->getData('dialog.currentQuestionNumber') ?? 0;
    }

    public static function stateClass(): string
    {
        return FlowState::class;
    }

    public function setCurrentQuestion(int $number): self
    {
        $this->currentQuestionNumber = $number;
        return $this;
    }

    public function nextQuestion(): ?IBotDialogQuestion
    {
        $this->currentQuestionNumber = $this->currentQuestionNumber + 1;
        $this->store();
        return $this->currentQuestion();
    }

    public function currentQuestion(): ?IBotDialogQuestion
    {
        return $this->questions[$this->currentQuestionNumber] ?? null;
    }

    protected function intro(): ?int
    {
        if (empty($this->introText)) {
            return null;
        }

        $message = $this->renderMessage($this->introText);

        if ($this->editAnswers) {
            $message .= <<<TEXT


_Все предоставленные ответы до отправки можно редактировать_
TEXT;
        }

        $this->botService->setTelegraphChat($this->state->telegram_user_id);
        $response = $this->botService->telegraph()->markdown($message)->withoutPreview()->send();

        return $response->telegraphMessageId();
    }

    protected function outro(): ?int
    {
        if (empty($this->outroText)) {
            return null;
        }

        $message = $this->renderMessage($this->outroText);

        if ($this->explicitAccept) {
            $message .= <<<TEXT


*Прошу проверить данные, и подтвердить отправку!*
TEXT;
            if ($this->editAnswers) {
                $message .= <<<TEXT


_Если что-то заполнено некорректно, ты можешь просто исправить сообщение с ответом на соответствующий вопрос_
TEXT;
            }
        }

        $this->botService->setTelegraphChat($this->state->telegram_user_id);

        $telegraph = $this->botService->telegraph();

        if ($this->outroMessageId) {
            $telegraph = $telegraph->edit($this->outroMessageId);
        }

        $telegraph = $telegraph->markdown($message)
            ->withoutPreview();

        if ($this->explicitAccept) {
            $keyboard = Keyboard::make()->buttons([
                Button::make('Подтверждаю')->param('state-id', $this->state->id)->param('accept', true)
            ]);
            $telegraph = $telegraph->keyboard($keyboard);
        }

        $response = $telegraph->send();

        return $response->telegraphMessageId();
    }

    public function handleCallbackQuery(CallbackQuery $callbackQuery): void
    {
        parent::handleCallbackQuery($callbackQuery);

        $this->botService->setTelegraphChat($this->state->telegram_user_id);

        $stateId = $callbackQuery->data()->get('state-id');
        if (!$stateId || !($stateId == $this->state->id)) {
            return;
        }

        if (!$this->currentQuestion() && $callbackQuery->data()->get('accept', false)) {
            if ($this->outroMessageId) {
                $this->botService->telegraph()->deleteKeyboard($this->outroMessageId)->send();
            }

            if ($this->explicitAcceptReply) {
                $reply = $this->renderMessage($this->explicitAcceptReply);
                $telegraph = $this->botService->telegraph();
                if ($this->outroMessageId) {
                    $telegraph = $telegraph->reply($this->outroMessageId);
                }
                $telegraph->markdownV2($reply)->send();
            }

            $this->succeed();
        }
    }

    public function question(string $name): ?IBotDialogQuestion
    {
        foreach ($this->questions as $question) {
            if ($question->name() == $name) {
                return $question;
            }
        }
        return null;
    }

    public function firstQuestion(): IBotDialogQuestion
    {
        $this->currentQuestionNumber = 0;
        $this->store();
        $firstQuestion = $this->currentQuestion();
        if ($firstQuestion) {
            return $firstQuestion;
        } else {
            throw new Exception('No first question!');
        }
    }

    public function store(): void
    {
        $this->state->setData('dialog.currentQuestionNumber', $this->currentQuestionNumber);
        parent::store();
    }

    public function succeed(): void
    {
        parent::succeed();

        if ($dispatchResultsWithEventClass = $this->dispatchResultsWith) {
            $dispatchResultsWithEventClass::dispatch($this);
        }
    }

    public function interrupt(int $code, ?string $reason = null): void
    {
        parent::interrupt($code, $reason);

        $this->botService->setTelegraphChat($this->state->telegram_user_id);
        $this->botService->telegraph()->markdown('Диалог прерван' . ($reason ? ": $reason" : ''))->send();
    }
}
