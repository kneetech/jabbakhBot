<?php

namespace Botflow\Exceptions;


final class YandexBotException extends Exception
{
    public static function missingBot(): YandexBotException
    {
        return new self("No YandexBot defined for this request");
    }

    public static function missingChat(): YandexBotException
    {
        return new self("No YandexChat defined for this request");
    }

    public static function noEndpoint(): YandexBotException
    {
        return new self("Trying to send a request without setting an endpoint");
    }
}
