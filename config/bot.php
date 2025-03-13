<?php

use App\Models\Bot\Actions\UnknownCommandAction;
use App\Models\Bot\Commands\HelloCommand;
use App\Models\Bot\Commands\HelpCommand;
use App\Models\Bot\Commands\StartCommand;
use App\Models\Bot\Commands\TestCommand;
use App\Models\Bot\Commands\TimeCommand;
use App\Models\Bot\Flows\DownloadVkVideoFlow;
use App\Models\Bot\Flows\SpeechToTextFlow;
use App\Models\Bot\Middleware\AuthenticateUser;

return [
    'token' => env('BOT_TOKEN'),

    'name' => env('BOT_NAME'),

    'first_work_hour' => env('BOT_FIRST_WORK_HOUR'),
    'last_work_hour' => env('BOT_LAST_WORK_HOUR'),

    'menu' => [
        'help'    => 'Что умеет этот бот',
    ],

    'middleware' => [
        AuthenticateUser::class,
    ],

    'commands' => [
        HelloCommand::class,
        HelpCommand::class,
        StartCommand::class,
        TestCommand::class,
        TimeCommand::class,
    ],

    'flows' => [
        SpeechToTextFlow::class,
        DownloadVkVideoFlow::class,
    ],

    'flowStateEvents' => [

    ],

    'unknownCommandAction' => UnknownCommandAction::class,

    'logChannel' => 'telegraph',

    'flowConfigs' => [

    ],
];
