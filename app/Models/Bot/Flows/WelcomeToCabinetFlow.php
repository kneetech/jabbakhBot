<?php

namespace App\Models\Bot\Flows;

use Botflow\Contracts\CommonBotFlow;
use Botflow\Contracts\IBotService;
use Botflow\Exceptions\RuntimeConfigurationErrorException;

class WelcomeToCabinetFlow extends CommonBotFlow
{

    protected int $telegramUserId;

    public function __construct(IBotService $botService, array $params = [])
    {
        parent::__construct($botService, $params);

        if (empty($this->params['telegram_user_id'])) {
            throw new RuntimeConfigurationErrorException('Required parameter telegram_user_id is missing');
        }

        $this->telegramUserId = $this->params['telegram_user_id'];
    }

    public function activate(): void
    {
        $this->botService->setTelegraphChat($this->telegramUserId);

        $this->botService->telegraph()->markdown('Добро пожаловать в личный кабинет!')->send();
    }
}
