<?php

namespace App\Models\Bot\Flows;

use Botflow\Contracts\CommonBotFlow;
use DefStudio\Telegraph\DTO\Message;
use Illuminate\Support\Facades\Log;

class Calculator extends CommonBotFlow
{

    public function handleChatMessage(Message $message): void
    {
        if (preg_match('/^((\d*\.?\d*)([+-\/*]))*(\d*\.?\d*)$/', $message->text())) {
            try {
                $formula = str_replace(',', '.', $message->text());
                $x = 0;
                eval("\$x={$formula};");
                $this->botService->telegraph()->markdown($x)->send();
                $this->botService->telegraphBot()->storage()->set("message.{$message->id()}.processed", true);
            } catch (\Exception $e) {
                Log::error("Не удалось посчитать '{$message->text()}': " . $e->getMessage());
            }
        }
    }
}
