<?php

namespace Botflow\Contracts;

use Botflow\Telegraph\Models\User;
use Botflow\Enums\Reaction;
use Botflow\Exceptions\Exception;
use Botflow\Exceptions\RuntimeConfigurationErrorException;
use Botflow\Exceptions\RuntimeUnexpectedErrorException;
use DefStudio\Telegraph\DTO\CallbackQuery;
use DefStudio\Telegraph\DTO\Message;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;

abstract class CommonDialog extends CommonBotFlowWithState
{

    protected bool $editAnswers = true;

    protected bool $explicitAccept = false;

    protected ?string $explicitAcceptReply = null;

    protected ?int $introMessageId = null;

    protected ?int $outroMessageId = null;

    protected ?User $user = null;

    public function __construct(IBotService $botService, array $params = [])
    {
        parent::__construct($botService, $params);

        if (empty($this->state->telegram_user_id) || empty($this->state->telegram_chat_id)) {
            throw new RuntimeConfigurationErrorException('Dialog flow state requires telegram_user_id and telegram_chat_id');
        }

        $this->state->monopolizing = true;

        $this->introMessageId = $this->state->getData('intro.message');
        $this->outroMessageId = $this->state->getData('outro.message');

        if ($telegramId = $this->state->telegram_user_id ?: $this->state->telegram_chat_id) {
            $userClass = $this->state()::userClass();
            $this->user = $userClass::query()->where('telegram_id', $telegramId)->first();
        }
    }

    public function answers(): array
    {
        return $this->state->getData('answers');
    }

    abstract public function question(string $name): ?IBotDialogQuestion;

    /**
     * @throws Exception
     */
    abstract public function firstQuestion(): IBotDialogQuestion;

    abstract public function currentQuestion(): ?IBotDialogQuestion;

    abstract public function nextQuestion(): ?IBotDialogQuestion;

    abstract public function setCurrentQuestion(int $number): self;

    /**
     * @throws Exception
     */
    protected abstract function intro(): ?int;

    protected abstract function outro(): ?int;

    public function renderMessage(string $text): string
    {
        return Blade::render($text, array_merge($this->state->data ?? [], [
            'current_question' => $this->currentQuestionNumber + 1,
            'questions_count'  => count($this->questions),
            'user'             => $this->user,
        ]));
    }

    /**
     * @throws Exception
     */
    public function start(): void
    {
        $this->introMessageId = $this->intro();

        $this->firstQuestion()->ask();

        $this->store();
    }

    public function accept(): void
    {
        if ($this->explicitAccept) {
            $this->outroMessageId = $this->outro();
            $this->store();
        } else {
            $this->succeed();
        }
    }

    public function succeed(): void
    {
        parent::succeed();

        if (!$this->explicitAccept) {
            $this->outroMessageId = $this->outro();
            $this->store();
        }
    }

    public function interrupt(int $code, ?string $reason = null): void
    {
        parent::interrupt($code, $reason);
    }

    protected function currentQuestionAnswers(
        array $initAnswers,
        ?IBotDialogQuestion $currentQuestion,
        mixed $answer,
    ): array {
        $answers = array_merge([], $initAnswers);
        $currentQuestionAnswers = $this->severalAnswersFromOneQuestion($currentQuestion, $answer);
        if (empty($currentQuestionAnswers)) {
            $answers[] = [
                $currentQuestion->name() => $answer,
            ];
        } else {
            $answers[] = $currentQuestionAnswers;
        }
        $this->state->setData("answers", $answers);
        $this->nextQuestion();
        return $answers;
    }

    public function handleEditedMessage(Message $message): void
    {
        parent::handleEditedMessage($message);

        if ($this->editAnswers) {

            $storedAnswers = $this->state->getData('answers') ?? [];
            $answerMessages = $this->state->getData('answerMessages') ?? [];
            $expectedAnswers = $this->state->getData('expectedAnswers') ?? $this->state->getParam("questions");

            if (in_array(strval($message->id()), array_keys($answerMessages))) {
                $questionName = $answerMessages[strval($message->id())];

                if ($question = $this->question($questionName)) {
                    $validationErrors = $question->validate($message->text());
                    if ($validationErrors->isEmpty()) {
                        $answers = $this->currentQuestionAnswers(
                            initAnswers:     $storedAnswers,
                            currentQuestion: $question,
                            answer:          $question->value($message->text()),
                        );

                        $this->botService->telegraph()->setMessageReactionObs($message->id(), Reaction::OK)->send();

                        if ($questionMessageId = $this->state->getData("questionMessages.{$questionName}")) {
                            $this->botService->telegraph()->deleteKeyboard($questionMessageId)->send();
                        }

                        $validationMessages = $this->state->getData("validationMessages.{$question->name()}") ?? [];
                        foreach ($validationMessages as $id => $msg) {
                            $this->botService->telegraph()->deleteMessage($id)->send();
                        }

                        $this->state->setData("validationMessages.{$question->name()}", []);

                        $wrongAnswerMessages = $this->state->getData("wrongAnswerMessages.{$question->name()}") ?? [];
                        foreach ($wrongAnswerMessages as $id => $questionName) {
                            $this->botService->telegraph()->deleteMessage($id)->send();
                        }
                        $this->state->setData("wrongAnswerMessages.{$question->name()}", []);

                        if (
                            count($storedAnswers) + 1 === count($expectedAnswers)
                            || count($answers) === count($expectedAnswers)
                        ) {
                            $this->outroMessageId = $this->outro();
                        }
                    } else {
                        $this->botService->telegraph()
                            ->setMessageReactionObs($message->id(), Reaction::HMM)->send();
                        foreach ($validationErrors->messages() as $messages) {
                            foreach ($messages as $msg) {
                                $validationMessage = $this->botService->telegraph()
                                    ->withoutPreview()
                                    ->reply($message->id())
                                    ->markdown($this->renderMessage($msg));
                                $response          = $validationMessage->send();
                                $this->state->setData(
                                    "validationMessages.{$question->name()}.{$response->telegraphMessageId()}",
                                    $validationMessage->toArray()
                                );
                            }
                        }
                    }
                }
            }

            $this->store();
        }
    }

    public function handleChatMessage(Message $message): void
    {
        parent::handleChatMessage($message);

        if (!$this->currentQuestion()) {
            Log::error("currentQuestion is null!");
            return;
        }

        $storedAnswers = $this->state->getData('answers') ?? [];
        $currentQuestion = $this->currentQuestion();
        $validationErrors = $currentQuestion->validate($message->text() ?: '');

        if ($validationErrors->isEmpty()) {
            $answers = $this->currentQuestionAnswers(
                initAnswers:     $storedAnswers,
                currentQuestion: $currentQuestion,
                answer:          $currentQuestion->value($message->text()),
            );

            $this->state->setData("answerMessages.{$message->id()}", $currentQuestion->name());

            if ($questionMessageId = $this->state->getData("questionMessages.{$currentQuestion->name()}")) {
                try {
                    $this->botService->telegraph()->deleteKeyboard($questionMessageId)->send();
                } catch (\Exception $e) {
                    // do nothing (rat style)
                }
            }

            $validationMessages = $this->state->getData("validationMessages.{$currentQuestion->name()}") ?? [];
            foreach ($validationMessages as $id => $msg) {
                $this->botService->telegraph()->deleteMessage($id)->send();
            }
            $this->state->setData("validationMessages.{$currentQuestion->name()}", []);

            $wrongAnswerMessages = $this->state->getData("wrongAnswerMessages.{$currentQuestion->name()}") ?? [];
            foreach ($wrongAnswerMessages as $id => $questionName) {
                $this->botService->telegraph()->deleteMessage($id)->send();
            }
            $this->state->setData("wrongAnswerMessages.{$currentQuestion->name()}", []);

            $currentQuestion->reply();

            $this->botService->telegraph()->setMessageReactionObs($message->id(), Reaction::OK)->send();

            $expectedAnswers = $this->state->getData('expectedAnswers') ?? $this->state->getParam("questions");

            if (count($answers) < count($expectedAnswers)) {
                $questionMessageId = $this->currentQuestion()->ask();
                $this->state->setData("questionMessages.{$this->currentQuestion()->name()}", $questionMessageId);
            } else {
                $this->accept();
            }
        } else {
            $this->botService->telegraph()->setMessageReactionObs($message->id(), Reaction::HMM)->send();

            $this->state->setData("wrongAnswerMessages.{$currentQuestion->name()}.{$message->id()}", $currentQuestion->name());
            foreach ($validationErrors->messages() as $messages) {
                foreach ($messages as $msg) {
                    $validationMessage = $this->botService->telegraph()
                        ->withoutPreview()
                        ->reply($message->id())
                        ->markdown($this->renderMessage($msg));
                    $response          = $validationMessage->send();
                    $this->state->setData(
                        "validationMessages.{$currentQuestion->name()}.{$response->telegraphMessageId()}",
                        $validationMessage->toArray()
                    );
                }
            }
        }

        $this->store();
    }

    /**
     * @throws RuntimeUnexpectedErrorException
     */
    public function handleCallbackQuery(CallbackQuery $callbackQuery): void
    {
        parent::handleCallbackQuery($callbackQuery);

        $stateId = $callbackQuery->data()->get('state-id');
        if (!$stateId || !($stateId == $this->state->id)) {
            return;
        }

        $skip = $callbackQuery->data()->get('skip', false);

        $storedAnswers = $this->state->getData('answers') ?? [];
        $currentQuestion = $this->currentQuestion();

        if ($skip) {
            $answers = $this->currentQuestionAnswers(
                initAnswers:     $storedAnswers,
                currentQuestion: $currentQuestion,
                answer:          null,
            );

            if ($questionMessageId = $this->state->getData("questionMessages.{$currentQuestion->name()}")) {
                $this->botService->telegraph()->deleteKeyboard($questionMessageId)->send();
                $this->botService->telegraph()->reply($questionMessageId)->message('Вопрос пропущен')->send();
            }

            $validationMessages = $this->state->getData("validationMessages.{$currentQuestion->name()}") ?? [];
            foreach ($validationMessages as $id => $msg) {
                $this->botService->telegraph()->deleteMessage($id)->send();
            }
            $this->state->setData("validationMessages.{$currentQuestion->name()}", []);

            $wrongAnswerMessages = $this->state->getData("wrongAnswerMessages.{$currentQuestion->name()}") ?? [];
            foreach ($wrongAnswerMessages as $id => $questionName) {
                $this->botService->telegraph()->deleteMessage($id)->send();
            }
            $this->state->setData("wrongAnswerMessages.{$currentQuestion->name()}", []);

            $currentQuestion->reply();

            $expectedAnswers = $this->state->getData('expectedAnswers') ?? $this->state->getParam("questions");

            if (count($answers) < count($expectedAnswers)) {
                $questionMessageId = $this->currentQuestion()->ask();
                $this->state->setData("questionMessages.{$this->currentQuestion()->name()}", $questionMessageId);
            } else {
                $this->accept();
            }
        } else {
            if ($pickedAnswer = $callbackQuery->data()->get('answer')) {
                $validationErrors = $currentQuestion->validate($pickedAnswer);

                if ($validationErrors->isEmpty()) {
                    $answer = $currentQuestion->value($pickedAnswer);
                    $answers = $this->currentQuestionAnswers(
                        initAnswers:     $storedAnswers,
                        currentQuestion: $currentQuestion,
                        answer:          $answer,
                    );

                    if ($questionMessageId = $this->state->getData("questionMessages.{$currentQuestion->name()}")) {
                        $this->botService->telegraph()->deleteKeyboard($questionMessageId)->send();

                        $this->botService->telegraph()->reply($questionMessageId)->message('Выбран ответ: ' . $currentQuestion->inlineOptions()[$answer])->send();
                    }

                    $currentQuestion->reply();

                    $expectedAnswers = $this->state->getData('expectedAnswers') ?? $this->state->getParam("questions");

                    if (count($answers) < count($expectedAnswers)) {
                        $questionMessageId = $currentQuestion->ask();
                        $this->state->setData("questionMessages.{$currentQuestion->name()}", $questionMessageId);
                    } else {
                        $this->accept();
                    }
                } else {
                    foreach ($validationErrors->messages() as $messages) {
                        foreach ($messages as $msg) {
                            $this->botService->telegraph()->markdown($this->renderMessage($msg))->send();
                            $validationMessage = $this->botService->telegraph()->markdown($this->renderMessage($msg));
                            $response          = $validationMessage->send();
                            $this->state->setData("validationMessages.{$currentQuestion->name()}.{$response->telegraphMessageId()}", $validationMessage->toArray());
                        }
                    }
                }
            }
        }

        $this->store();
    }

    /**
     * @throws RuntimeUnexpectedErrorException
     */
    public function store(): void
    {

        if (!empty($this->introMessageId)) {
            $this->state->setData('intro.message', $this->introMessageId);
        }

        if (!empty($this->outroMessageId)) {
            $this->state->setData('outro.message', $this->outroMessageId);
        }

        parent::store();
    }

    public function remind(): void
    {
    }

    public function severalAnswersFromOneQuestion(IBotDialogQuestion $question, ?string $answer): array
    {
        return $question->generatedAnswers($answer);
    }
}
