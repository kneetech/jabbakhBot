<?php

namespace Botflow\Traits;

use Illuminate\Support\Str;

trait HasJsonArrayWithoutLineBreaks
{
    protected function jsonArrayWithoutLineBreaks(array $array): array
    {
        return json_decode($this->replaceLineBreaks(json_encode($array)), true) ?? [];
    }

    private function replaceLineBreaks(string $text): string
    {
        return Str::replace(
            ["\r\n", "\n", "\r", '\r\n', '\n', '\r'],
            ' ',
            $text,
        );
    }
}
