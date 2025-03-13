<?php

namespace Botflow\Contracts;

readonly class InteractiveMessageButton
{

    public function __construct(
        private string  $title,
        private array   $params,
        private ?string $command = null,
        private ?string $url = null
    )
    {
    }

    public function title(): string
    {
        return $this->title;
    }

    public function params(): array
    {
        return $this->params;
    }

    public function command(): ?string
    {
        return $this->command;
    }

    public function url(): ?string
    {
        return $this->url;
    }
}

