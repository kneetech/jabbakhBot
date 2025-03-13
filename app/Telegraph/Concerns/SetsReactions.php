<?php

namespace App\Telegraph\Concerns;

use App\Telegraph\BotflowTelegraph;
use Botflow\Enums\Reaction;

/**
 * @mixin BotflowTelegraph
 */
trait SetsReactions
{

    public function setMessageReactionObs(int $messageId, Reaction $reaction = Reaction::HEART): self
    {
        $telegraph = clone $this;

        $telegraph->endpoint = self::ENDPOINT_SET_MESSAGE_REACTION;

        $telegraph->data['message_id'] = $messageId;
        $telegraph->data['chat_id'] = $telegraph->getChatId();
        $telegraph->data['reaction'] = [['type' => 'emoji', 'emoji' => $reaction->value]];
        $telegraph->data['is_big'] = true;

        return $telegraph;
    }

    /**
     * @return Reaction[]
     */
    public function getFunnyReactions(): array
    {
        return [
            Reaction::THUMBS_UP,
            Reaction::HEART,
            Reaction::FIRE,
            Reaction::KISSES,
            Reaction::CLAP_HANDS,
            Reaction::OK,
            Reaction::PEACE,
            Reaction::SMILE,
            Reaction::WHALE,
            Reaction::TOP,
            Reaction::PRIZE,
            Reaction::STRAWBERRY,
            Reaction::KISS,
            Reaction::GHOST,
            Reaction::WORK,
            Reaction::SEE,
            Reaction::SAINT,
            Reaction::HANDSHAKE,
            Reaction::WRITING_HAND,
            Reaction::THANK_YOU,
            Reaction::YES_SIR,
            Reaction::TONGUE,
            Reaction::EASTER_ISLAND,
            Reaction::COOL,
            Reaction::UNICORN,
            Reaction::AIR_KISS,
            Reaction::COOL_GUY,
            Reaction::CHUPAKABRA
        ];
    }

    public function getConfusedReactions(): array
    {
        return [
            Reaction::HMM,
            Reaction::CONFUSED,
            Reaction::SEE,
            Reaction::DOWN_KNOW_WOMAN,
            Reaction::DONT_KNOW,
            Reaction::DONT_KNOW_MAP,
        ];
    }
}
