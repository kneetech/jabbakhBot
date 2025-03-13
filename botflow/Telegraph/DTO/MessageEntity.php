<?php

namespace Botflow\Telegraph\DTO;

use Botflow\Telegraph\Enums\MessageEntityType;
use DefStudio\Telegraph\DTO\User;
use Illuminate\Contracts\Support\Arrayable;

class MessageEntity implements Arrayable
{

    public function __construct(
        private MessageEntityType $type,
        private int $offset,
        private int  $length,
        private ?string $url = null,
        private ?User $user = null,
        private ?string $language = null,
        private ?string $customEmojiId = null
    )
    {
    }

    public function toArray()
    {
        $result = [
            'type' => $this->type->value,
            'offset' => $this->offset,
            'length' => $this->length,
        ];

        if (!empty($this->url)) {
            $result['url'] = $this->url;
        }

        if (!empty($this->user)) {
            $result['user'] = $this->user->toArray();
        }

        if (!empty($this->language)) {
            $result['language'] = $this->language;
        }

        if (!empty($this->customEmojiId)) {
            $result['custom_emoji_id'] = $this->customEmojiId;
        }

        return $result;
    }
}
