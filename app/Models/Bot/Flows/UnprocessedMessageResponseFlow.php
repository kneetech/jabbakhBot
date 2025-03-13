<?php

namespace App\Models\Bot\Flows;

use Botflow\Contracts\CommonBotFlow;
use Botflow\Exceptions\Exception;
use Botflow\Flows\SetFunnyReactionToJustReceivedMessage;
use DefStudio\Telegraph\DTO\Message;
use DefStudio\Telegraph\Exceptions\StorageException;

class UnprocessedMessageResponseFlow extends CommonBotFlow
{

    public function handleChatMessage(Message $message): void
    {
        try {
            $storageDriver = $this->botService->telegraphBot()->storage();
        } catch (StorageException $e) {
            throw new Exception($e->getMessage());
        }

        if ($storageDriver->get("message.{$message->id()}.processed")) {
            return;
        }

        $this->botService->addFlow(SetFunnyReactionToJustReceivedMessage::class);
    }
}
