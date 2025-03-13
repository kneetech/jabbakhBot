<?php

namespace Botflow\Jobs;

use Botflow\Contracts\CommonDialog;
use Botflow\Contracts\IBotService;
use Botflow\Contracts\IFlowStateRepository;
use Botflow\Exceptions\Exception;
use Botflow\Exceptions\RuntimeDataInconsistencyErrorException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * @throws Exception
 * @method static dispatch(int $dialogStateId, string $questionName, int $questionMessageId, string $reminderText)
 */
class DialogQuestionReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(
        protected int    $dialogStateId,
        protected string $questionName,
        protected int    $questionMessageId,
        protected string $reminderText
    )
    {
        //
    }

    /**
     * @throws Exception
     */
    public function handle(IBotService $botService, IFlowStateRepository $flowStateRepository): void
    {
        /** @var CommonDialog|null $dialog */
        $dialog = $flowStateRepository->restoreFlow($this->dialogStateId);

        // если dialogStateId не валиден
        if (empty($dialog) || !is_subclass_of($dialog, CommonDialog::class)) {
            throw new RuntimeDataInconsistencyErrorException('Невалидный dialogStateId: ' . $dialog->state()->telegram_chat_id);
        }

        // если диалог уже не активен
        if (!$dialog->state()->isActive()) {
            return;
        }

        $answers = $dialog->state()->getData('answers');

        if (empty($answers)) {
            $dialog->remind();
            return;
        }

        // если ответ на вопрос уже дан
        if (isset($answers[$this->questionName])) {
            return;
        }

        if (count($answers) >= 1) {
            $botService->setTelegraphChat($dialog->state()->telegram_chat_id);

            $botService->telegraph()
                ->reply($this->questionMessageId)
                ->message($dialog->renderMessage($this->reminderText))
                ->send();
        }
    }
}
