<?php

namespace Botflow\Exceptions;

use Exception;

class YandexWebhookException extends Exception
{
    public static function invalidAction(string $action): YandexWebhookException
    {
        return new self("No Yandex Bot Webhook handler defined for received action: $action");
    }

    public static function invalidCommand(string $command): YandexWebhookException
    {
        return new self("No Yandex Bot Webhook handler defined for received $command: $command");
    }

    public static function invalidData(string $description): YandexWebhookException
    {
        return new self("Invalid data: $description");
    }

    public static function invalidScheme(): YandexWebhookException
    {
        return new self("You application must have a secure (https) url in order to accept webhook calls");
    }
}
