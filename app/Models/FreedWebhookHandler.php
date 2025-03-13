<?php

namespace App\Models;

use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Telegraph;

class FreedWebhookHandler extends WebhookHandler
{

    public function handleMessage(): void
    {
        parent::handleMessage();

        $this->reply('Пошёл нахуй!');
    }
}
