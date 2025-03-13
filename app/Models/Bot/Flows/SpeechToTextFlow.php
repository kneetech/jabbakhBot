<?php

namespace App\Models\Bot\Flows;

use App\Jobs\ProcessVoiceJob;
use Botflow\Contracts\CommonBotFlow;
use DefStudio\Telegraph\DTO\Message;

class SpeechToTextFlow extends CommonBotFlow
{

    /**
     */
    public function handleChatMessage(Message $message): void
    {

        if ($voice = $message->voice()) {
            ProcessVoiceJob::dispatch($message->from()->id(), $voice->id(), $message->id());
        }
    }

    /**
     */
    public function handleChannelPost(Message $message): void
    {
        if ($voice = $message->voice()) {
            ProcessVoiceJob::dispatch($message->chat()->id(), $voice->id(), $message->id());
        }
    }


}
