<?php

namespace Botflow\Contracts;

use Botflow\Exceptions\Exception;

interface IBotAction
{

    public function __construct(IBotService $botService, array $params = []);

    /**
     * Executes in console commands after common behavior
     * @return void
     */
    public function consoleBehavior(): void;

    /**
     * Executes in private telegram chat after common behavior
     * @return void
     * @throws Exception
     */
    public function telegramBehavior(): void;
}
