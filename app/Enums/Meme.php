<?php

namespace App\Enums;

use Botflow\Enums\EnumToArray;

enum Meme: string
{
    use EnumToArray;

    case NOSEDIVE = 'images/memes/nose-dive-1.png';

    case SCEPTICISM = 'images/memes/scepticism-1.png';

    case GACHIMUCHI = 'images/memes/gachimuchi.png';

    case YOUWANNAFIGHT = 'images/memes/you-wanna-fight.jpg';

    public function path(): string
    {
        return resource_path($this->value);
    }
}
