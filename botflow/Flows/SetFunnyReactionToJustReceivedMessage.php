<?php

namespace Botflow\Flows;

use Botflow\Contracts\CommonBotFlow;
use Botflow\Telegraph\DTO\Update;
use DefStudio\Telegraph\Enums\ChatActions;
use DefStudio\Telegraph\Exceptions\TelegraphException;
use Illuminate\Support\Arr;

class SetFunnyReactionToJustReceivedMessage extends CommonBotFlow
{

    /**
     * @throws TelegraphException
     */
    public function handleUpdate(Update $update): void
    {
        if ($message = $update->message()) {
            $telegraph = $this->botService->telegraph();

            $telegraph->chatAction(ChatActions::CHOOSE_STICKER)->send();
            $telegraph->setMessageReactionObs(
                $message->id(),
                Arr::random($telegraph->getFunnyReactions())
            )->dispatch()->delay(1);
        }
    }
}
