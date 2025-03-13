<?php

namespace App\Telegraph\Concerns;

use App\Telegraph\BotflowTelegraph;
use Botflow\Telegraph\DTO\MessageEntity;
use Illuminate\Support\Collection;

/**
 * @mixin BotflowTelegraph
 */
trait ComposesMessagesExtension
{

    /**
     * @param array|Collection $entities
     *
     * @return BotflowTelegraph|ComposesMessagesExtension
     */
    public function entities(array|Collection $entities): self
    {
        $telegraph = clone $this;

        if (is_array($entities)) {
            $entities = collect($entities);
        }

        unset($telegraph->data['parse_mode']);

        data_set(
            $telegraph->data,
            'entities',
            $entities->map(fn (MessageEntity  $entity) => $entity->toArray())->toArray()
        );

        return $telegraph;
    }

    public function editKeyboard(int $messageId): self
    {
        $telegraph = clone $this;

        $telegraph->endpoint = self::ENDPOINT_EDIT_MESSAGE_KEYBOARD;
        $telegraph->data['message_id'] = $messageId;
        $telegraph->data['chat_id'] = $this->getChatId();

        return $telegraph;
    }

    public function caption(string $caption): self
    {
        $telegraph = clone $this;

        $telegraph->data['caption'] = $caption;
        $telegraph->data['chat_id'] = $this->getChatId();

        return $telegraph;
    }
}
