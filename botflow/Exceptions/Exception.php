<?php

namespace Botflow\Exceptions;

class Exception extends \Exception
{
    public function string(): string
    {
        return "\n"
            . $this->getFile() . ':' . $this->getLine() . "\n\n"
            . $this->getMessage() . "\n\n"
            . $this->getTraceAsString();
    }
}
