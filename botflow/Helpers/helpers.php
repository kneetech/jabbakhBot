<?php

if (!function_exists('escape_markdown')) {
    function escape_markdown(string $text): string
    {
        return str_replace(['_', '*', '`', '[', ']'], ['\_', '\*', '\`', '\[', '\]'], $text);
    }
}

if (!function_exists('escape_markdown_v2')) {
    function escape_markdown_v2(string $text): string
    {
        return str_replace(
            ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'],
            ['\_', '\*', '\[', '\]', '\(', '\)', '\~', '\`', '\>', '\#', '\+', '\-', '\=', '\|', '\{', '\}', '\.', '\!'],
            $text
        );
    }
}

if (!function_exists('plural')) {
    function plural(array $endings, int $number): string
    {
        $cases = [2, 0, 1, 1, 1, 2];
        $n     = $number;
        return sprintf($endings[($n % 100 > 4 && $n % 100 < 20) ? 2 : $cases[min($n % 10, 5)]], $n);
    }
}
