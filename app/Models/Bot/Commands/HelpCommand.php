<?php

namespace App\Models\Bot\Commands;

use Botflow\Contracts\CommonBotCommand;

class HelpCommand extends CommonBotCommand
{

    private string $helpText;

    private array $aves = [
        'Хай\!',
        'Хола\!',
        'Дароф\!',
        'День добрый.',
        'Приветствую.',
        'Рад видеть\!',
        'Здарова\!',
        'Хоба\!',
        'Драсьте',
        'Здравствуте\!',
        'Здравствуй',
        'Божур, ёпта \)'
    ];

    protected function boot(): void
    {
    }

    public function telegramBehavior(): void
    {
        $ave = $this->aves[array_rand($this->aves)];

        $this->botService->telegraph()
            ->markdownV2(<<<MD
$ave

Это команда /help

Документация в разработке
MD
            )
            ->send();
    }

    public function consoleBehavior(): void
    {
        $ave = $this->aves[array_rand($this->aves)];

        $this->info(<<<MD
$ave

Это команда /help

Документация в разработке
MD
        );
    }

    public function alias(): string
    {
        return 'help';
    }

    public function helpMessage(): string
    {
        return 'Справка по боту';
    }
}
