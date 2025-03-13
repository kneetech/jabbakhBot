<?php

namespace Botflow\Contracts;

use App\Telegraph\BotflowTelegraph;
use Botflow\Exceptions\Exception;
use DefStudio\Telegraph\DTO\User;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;

interface IBotService
{

    public function setTelegraphUser(User $user): void;

    public function telegraphUser(): ?User;

    public function setTelegraphBot(TelegraphBot $telegraphBot): void;

    public function telegraphBot(): TelegraphBot;

    /**
     * @param TelegraphChat|int $telegraphChat chat object or chat id (integer)
     * @throws Exception
     */
    public function setTelegraphChat($telegraphChat): void;

    /**
     * @throws Exception
     */
    public function telegraphChat(): ?TelegraphChat;

    public function nextFlow(): ?IBotFlow;

    /**
     * @throws Exception
     */
    public function addFlow(string $flowClass, array $params = []): self;

    /**
     * @throws Exception
     */
    public function startFlow(string $flowClass, ?int $telegram_user_id = null, ?int $telegram_chat_id = null, array $params = []): self;

    /**
     * @throws Exception
     */
    public function startFlowInPrivateChat(
        string|IDialogConfig $dialogConfig,
        ?int                 $telegram_user_id,
        array                $params = [],
        array                $listeners = [],
    ): void;

    public function addCommand(string $commandClass, array $params = []): self;

    public function getCommand(string $alias): ?IBotCommand;

    public function addAction(string $actionClass, array $params = []): self;

    public function nextAction(): ?IBotAction;

    public function addMiddleware(string $middlewareClass, array $params = []): self;

    public function nextMiddleware(): ?IBotMiddleware;

    public function unknownCommandAction(): ?IBotAction;

    /**
     * @return BotflowTelegraph
     */
    public function telegraph();
}
