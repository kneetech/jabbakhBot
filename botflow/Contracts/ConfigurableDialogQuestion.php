<?php

namespace Botflow\Contracts;

use Botflow\Exceptions\RuntimeConfigurationErrorException;
use Botflow\Jobs\DialogQuestionReminderJob;
use Botflow\Traits\HasWorkSchedule;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;

class ConfigurableDialogQuestion implements IBotDialogQuestion
{
    use HasWorkSchedule;

    protected string $name;

    protected string $question;

    protected ?string $reply = null;

    protected ?array $reminders = [
        [
            'delay' => 30,
            'message' => 'Пожалуйста, давай вернёмся к вопросу'
        ],
        [
            'delay' => 60 * 24,
            'message' => 'Вернёмся к вопросу?'
        ],
    ];

    protected bool $optional = false;

    protected array $rules = [];

    protected ?array $answerOptions = null;

    public function __construct(protected ConfigurableDialog $dialog, protected IBotService $botService, array $configuration = [])
    {

        $configurationValidator = Validator::make($configuration, [
            'name'          => 'required|string|max:255|regex:/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/',
            'ask'           => 'required|string',
            'reply'         => 'string',
            'reminders'     => 'array',
            'rules'         => 'array',
            'optional'      => 'boolean',
            'answerOptions' => 'array',
        ]);

        if ($configurationValidator->fails()) {
            throw new RuntimeConfigurationErrorException(
                "ConfigurableDialogQuestion configuration errors: " .
                $configurationValidator->errors()->toJson()
            );
        }

        $validatedConfig = $configurationValidator->validated();

        $this->name     = $validatedConfig['name'];
        $this->question = $validatedConfig['ask'];

        if (isset($validatedConfig['reply'])) {
            $this->reply = $validatedConfig['reply'];
        }

        if (isset($validatedConfig['reminders'])) {
            $this->reminders = [];
            foreach ($validatedConfig['reminders'] as $reminder) {
                $reminderValidator = Validator::make($reminder, [
                    'delay' => 'required|integer|min:1',
                    'message' => 'required|string'
                ]);

                if ($reminderValidator->fails()) {
                    throw new RuntimeConfigurationErrorException(
                        "ConfigurableDialogQuestion reminder configuration errors: " .
                        $reminderValidator->errors()->toJson()
                    );
                }

                $this->reminders[] = $reminderValidator->validated();
            }
        }

        if (isset($validatedConfig['rules'])) {
            $this->rules = $validatedConfig['rules'];
        }

        if (isset($validatedConfig['optional'])) {
            $this->optional = $validatedConfig['optional'];
        }

        if (isset($validatedConfig['answerOptions'])) {
            $this->answerOptions = $validatedConfig['answerOptions'];
        }
    }

    public function name(): string
    {
        return $this->name;
    }

    public function messageText(): string
    {
        return $this->dialog->renderMessage(<<<TEXT
        @if (\$questions_count > 1)
        _Вопрос {{ \$current_question }} из {{ \$questions_count }}_

        $this->question
        @else
        $this->question
        @endif
        TEXT);
    }

    public function ask(): ?int
    {
        $message = $this->botService->telegraph()->markdown($this->messageText());

        $buttons = [];

        if ($this->optional) {
            $buttons[] = Button::make("\xF0\x9F\x92\xA4  Пропустить вопрос")->param('skip', true)->param('state-id', $this->dialog->state()->id);
        }

        if (!empty($this->answerOptions)) {
            foreach ($this->answerOptions as $answerOptionKey => $answerOptionValue) {
                $buttons[] = Button::make($answerOptionValue)->param('answer', $answerOptionKey)->param('state-id', $this->dialog->state()->id);
            }
        }

        if (!empty($buttons)) {
            $message = $message->keyboard(Keyboard::make()->buttons($buttons));
        }

        $response = $message->send();

        foreach ($this->reminders as $reminder) {
            DialogQuestionReminderJob::dispatch(
                    $this->dialog->state()->id,
                    $this->name,
                    $response->telegraphMessageId(),
                    $reminder['message'])
                ->delay(self::nearestWorkSecondAfterDelay(now(), $reminder['delay'] ?? 0));
        }

        return $response->telegraphMessageId();
    }

    public function validate(string $answer): MessageBag
    {
        $messages = new MessageBag();

        foreach ($this->rules as $rule) {
            $rules = is_string($rule['rule']) ? explode('|', $rule['rule']) : $rule['rule'];
            array_unshift($rules, 'required');

            $validator = Validator::make(['answer' => $answer], ['answer' => $rules]);
            if ($validator->fails()) {
                $messages->add($this->name(), $rule['message']);
                break;
            }
        }

        if (empty($this->rules) && !empty($this->answerOptions)) {
            if (!in_array($answer, array_keys($this->answerOptions))) {
                $messages->add($this->name(), 'Пожалуйста, выбери ответ из предложенных вариантов');
            }
        }

        return $messages;
    }

    public function inlineOptions(): array
    {
        return $this->answerOptions;
    }

    public function reply(): ?int
    {
        if ($this->reply) {
            $message  = $this->dialog->renderMessage($this->reply);
            $response = $this->botService->telegraph()->markdown($message)->send();

            return $response->telegraphMessageId();
        } else {
            return null;
        }
    }

    public function value(string $answer): mixed
    {
        return $answer;
    }

    public function generatedAnswers(?string $answer): array
    {
        return [];
    }
}
