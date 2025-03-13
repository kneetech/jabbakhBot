<?php

namespace App\Providers;

use Opcodes\LogViewer\Logs\Log;

class BotflowLog extends Log
{
    public static string $name = 'BotflowLog';
    public static string $regex = '/^\[(?P<datetime>[\d\-+ :]+)\] (?P<environment>[a-z]*)\.(?P<level>[a-zA-Z]+): (?P<arguments>(?:\[[^\[\]]*\]){0,8}) (?P<message>\N*)$/';

    public static array $columns = [
        ['label' => 'Severity', 'data_path' => 'level'],
        ['label' => 'Datetime', 'data_path' => 'datetime'],
        ['label' => 'Env', 'data_path' => 'extra.environment'],
        ['label' => 'Arguments', 'data_path' => 'extra.arguments'],
    ];

    protected function fillMatches(array $matches = []): void
    {
        parent::fillMatches($matches);
        $this->extra['environment'] = $matches['environment'] ?? '';
        $this->extra['arguments'] = $matches['arguments'] ?? '';
        $this->context = json_decode($matches['message'] ?? '', true) ?? [];
    }
}
