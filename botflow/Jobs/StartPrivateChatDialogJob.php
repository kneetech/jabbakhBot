<?php

namespace Botflow\Jobs;

use Botflow\Contracts\IDialogConfig;
use Botflow\Contracts\IBotService;
use Botflow\Exceptions\Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * @method static PendingDispatch dispatch(int $telegramUserId, string $alias, array $params, array $listeners = [])
 */
class StartPrivateChatDialogJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(
        protected int                  $telegramUserId,
        protected string|IDialogConfig $dialogConfig,
        protected array                $params,
        protected array                $listeners = []
    ) {
    }

    public function handle(IBotService $botService): void
    {
        $params = $this->params;

        $params['telegram_user_id'] = $this->telegramUserId;
        $params['telegram_chat_id'] = $this->telegramUserId;

        try {
            $botService->startFlowInPrivateChat(
                dialogConfig:     $this->dialogConfig,
                telegram_user_id: $this->telegramUserId,
                params:           $params,
                listeners:        $this->listeners
            );
        } catch (Exception $e) {
            Log::error($e->string());
        }
    }
}
