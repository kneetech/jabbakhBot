<?php

namespace Botflow\Contracts;

use Illuminate\Support\MessageBag;

interface IBotDialogQuestion
{

    public function name(): string;

    public function messageText(): string;

    public function ask(): ?int;

    public function validate(string $answer): MessageBag;

    public function inlineOptions(): array;

    public function reply(): ?int;

    public function value(string $answer): mixed;

    public function generatedAnswers(?string $answer): array;
}
