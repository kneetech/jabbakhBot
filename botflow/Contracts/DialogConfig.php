<?php

namespace Botflow\Contracts;

abstract readonly class DialogConfig implements IDialogConfig
{
    abstract public function config(): array;
}
