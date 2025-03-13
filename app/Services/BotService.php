<?php

namespace App\Services;

use App\Facades\Auth;
use App\Telegraph\BotflowTelegraph;
use Botflow\Contracts\CommonDialog;
use Botflow\Contracts\Concerns\IRequireAuth;
use Botflow\Contracts\Concerns\ISingletonFlow;
use Botflow\Contracts\ConfigurableDialog;
use Botflow\Contracts\ConfigurableDialogQuestion;
use Botflow\Contracts\IBotAction;
use Botflow\Contracts\IBotCommand;
use Botflow\Contracts\IBotDialogQuestion;
use Botflow\Contracts\IBotFlow;
use Botflow\Contracts\IBotMiddleware;
use Botflow\Contracts\IBotService;
use Botflow\Contracts\IDialogConfig;
use Botflow\Contracts\IFlowStateRepository;
use Botflow\Exceptions\Exception;
use Botflow\Exceptions\RuntimeAuthenticationErrorException;
use Botflow\Exceptions\RuntimeConfigurationErrorException;
use Botflow\Helpers\JSON;
use Botflow\Telegraph\DTO\Update;
use DefStudio\Telegraph\DTO\User;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BotService implements IBotService
{

    use InteractsWithIO;

    protected ?IBotAction $unknownCommandAction = null;

    protected BotflowTelegraph $telegraph;

    private User $user;

    private TelegraphBot $telegraphBot;

    private ?TelegraphChat $telegraphChat = null;

    /** @var IBotFlow[] */
    private array $flows = [];

    /** @var IBotCommand[] */
    private array $commands = [];

    /** @var IBotAction[] */
    private array $actions = [];

    /** @var IBotMiddleware[] */
    private array $middleware = [];

    protected IFlowStateRepository $flowStateRepository;

    protected array $flowConfigs = [];

    /**
     * @throws RuntimeConfigurationErrorException
     * @throws \ReflectionException
     */
    public function __construct(
        array   $coreMiddleware,
        array   $coreCommands,
        array   $coreFlows,
        ?string $unknownCommandActionClass = null,
        array   $flowConfigs = []
    )
    {
        $this->telegraph           = app('telegraph');
        $this->flowStateRepository = app(IFlowStateRepository::class);

        foreach ($coreMiddleware as $key => $value) {
            $coreCommandClass  = is_int($key) ? $value : $key;
            $coreCommandParams = is_array($value) ? $value : [];
            $this->addMiddleware($coreCommandClass, $coreCommandParams);
        }

        foreach ($coreCommands as $key => $value) {
            $coreCommandClass  = is_int($key) ? $value : $key;
            $coreCommandParams = is_array($value) ? $value : [];
            $this->addCommand($coreCommandClass, $coreCommandParams);
        }

        foreach ($coreFlows as $key => $value) {
            $coreFlowClass  = is_int($key) ? $value : $key;
            $coreFlowParams = is_array($value) ? $value : [];
            $this->addFlow($coreFlowClass, $coreFlowParams);
        }

        if (!empty($unknownCommandActionClass)) {
            if (is_subclass_of($unknownCommandActionClass, IBotAction::class)) {
                $this->unknownCommandAction = new $unknownCommandActionClass($this);
            } else {
                throw new RuntimeConfigurationErrorException("UnknownCommandAction class must implement IBotAction interface");
            }
        }

        $this->validateFlowConfigs($flowConfigs);
        $this->flowConfigs = $flowConfigs;
    }

    public function setTelegraphBot(TelegraphBot $telegraphBot): void
    {
        $this->telegraph    = $this->telegraph->bot($telegraphBot);
        $this->telegraphBot = $telegraphBot;
    }

    /**
     * @param TelegraphChat|int $telegraphChat chat object or chat id (integer)
     * @throws Exception
     */
    public function setTelegraphChat($telegraphChat): void
    {
        if (is_a($telegraphChat, TelegraphChat::class)) {
            $this->telegraph     = $this->telegraph->clear()->chat($telegraphChat);
            $this->telegraphChat = $telegraphChat;
        } else {
            try {
                /** @var TelegraphChat $telegraphChat */
                $telegraphChat = TelegraphChat::query()
                    ->where('telegraph_bot_id', $this->telegraphBot->id)
                    ->where('chat_id', $telegraphChat)
                    ->firstOrFail();
            } catch (ModelNotFoundException) {
                throw new Exception('Chat is not found!');
            }
            $this->telegraph     = $this->telegraph->clear()->chat($telegraphChat);
            $this->telegraphChat = $telegraphChat;
        }
    }

    public function telegraphBot(): TelegraphBot
    {
        return $this->telegraphBot;
    }

    public function telegraphChat(): ?TelegraphChat
    {
        if (empty($this->telegraphChat) && !app()->runningInConsole()) {
            /** @var Update $update */
            $update = app(Update::class);
            $chatId = null;
            if (
                ($message = $update->message()) ||
                ($message = $update->editedMessage()) ||
                ($message = $update->channelPost()) ||
                ($message = $update->editedChannelPost())
            ) {
                $chatId = $message->chat()->id();
            } elseif ($callbackQuery = $update->callbackQuery()) {
                $chatId = $callbackQuery->message()?->chat()->id();
            }

            if ($chatId) {
                $this->telegraphChat = TelegraphChat::query()->where('chat_id', $chatId)->first();
            }
        }

        return $this->telegraphChat;
    }

    public function telegraph(): BotflowTelegraph
    {
        return $this->telegraph;
    }

    public function nextFlow(): ?IBotFlow
    {
        return array_shift($this->flows);
    }

    public function addFlow(string $flowClass, array $params = []): self
    {
        if (!is_subclass_of($flowClass, IBotFlow::class)) {
            throw new RuntimeConfigurationErrorException("Flow \"{$flowClass}\" must implement IBotFlow interface \"" . IBotFlow::class . "\"");
        }

        $requireAuthentication = in_array(IRequireAuth::class, class_implements($flowClass) ?: []);
        if ($requireAuthentication && Auth::guest()) {
            Log::debug("Flow {$flowClass} requires authentication");
            return $this;
        }

        /** @var IBotFlow $flow */
        $flow = new $flowClass($this, $params);

        $this->flows[] = $flow;

        return $this;
    }

    public function startFlow(string $flowClass, ?int $telegram_user_id = null, ?int $telegram_chat_id = null, array $params = []): IBotService
    {
        if (!is_subclass_of($flowClass, IBotFlow::class)) {
            throw new RuntimeConfigurationErrorException("Flow \"{$flowClass}\" must implement IBotFlow interface \"" . IBotFlow::class . "\"");
        }

        $requireAuthentication = in_array(IRequireAuth::class, class_implements($flowClass) ?: []);
        if ($requireAuthentication && Auth::guest()) {
            throw new RuntimeAuthenticationErrorException("Flow {$flowClass} requires authentication");
        }

        $isASingleton = in_array(ISingletonFlow::class, class_implements($flowClass) ?: []);
        /** @var ?IBotFlow $flow */
        $flow = null;
        if ($isASingleton) {
            $flowState = $this->flowStateRepository->getActiveFlowStatesForUser(
                $telegram_user_id,
                $telegram_chat_id,
                $flowClass,
            )->first();

            if (!empty($flowState)) {
                $flow = new $flowClass($this, array_merge(['id' => $flowState->id], $params));
            }
        }

        if (empty($flow)) {
            $flow = new $flowClass($this, array_merge($params, [
                'telegram_user_id' => $telegram_user_id,
                'telegram_chat_id' => $telegram_chat_id ?: $telegram_user_id,
            ]));
        }

        $flow->activate();

        return $this;
    }

    /**
     * @throws RuntimeConfigurationErrorException
     */
    public function addCommand(string $commandClass, array $params = []): IBotService
    {

        if (!is_subclass_of($commandClass, IBotCommand::class)) {
            throw new RuntimeConfigurationErrorException("Command \"{$commandClass}\" must implement IBotCommand interface");
        }

        /** @var IBotCommand $command */
        $command = new $commandClass($this, $params);

        if (!isset($this->commands[$command->alias()])) {
            $this->commands[$command->alias()] = $command;
        } else {
            throw new RuntimeConfigurationErrorException("Command alias must be unique");
        }

        return $this;
    }

    public function getCommand(string $alias): ?IBotCommand
    {
        return $this->commands[$alias] ?? null;
    }

    public function unknownCommandAction(): ?IBotAction
    {
        return $this->unknownCommandAction;
    }

    /**
     * @throws RuntimeConfigurationErrorException
     */
    public function addAction(string $actionClass, array $params = []): IBotService
    {
        if (!is_subclass_of($actionClass, IBotAction::class)) {
            throw new RuntimeConfigurationErrorException("Action must implement IBotAction interface");
        }

        $requireAuthentication = in_array(IRequireAuth::class, class_implements($actionClass) ?: []);
        if (app()->runningInConsole() && $requireAuthentication && Auth::guest()) {
            $this->warn("Action {$actionClass} skipped for console, as it requires authentication");
            return $this;
        }

        $action = new $actionClass($this, $params);

        $this->actions[] = $action;

        return $this;
    }

    public function nextAction(): ?IBotAction
    {
        return array_shift($this->actions);
    }

    public function addMiddleware(string $middlewareClass, array $params = []): self
    {
        if (!is_subclass_of($middlewareClass, IBotMiddleware::class)) {
            throw new RuntimeConfigurationErrorException("Command must implement IBotMiddleware interface");
        }

        $requireAuthentication = in_array(IRequireAuth::class, class_implements($middlewareClass) ?: []);
        if ($requireAuthentication && Auth::guest()) {
            Log::debug("Middleware {$middlewareClass} requires authentication");
            return $this;
        }


        $middleware = new $middlewareClass($this, $params);

        $this->middleware[] = $middleware;

        return $this;
    }

    public function nextMiddleware(): ?IBotMiddleware
    {
        return array_shift($this->middleware);
    }

    public function startFlowInPrivateChat(string|IDialogConfig $dialogConfig,
                                           ?int                 $telegram_user_id,
                                           array                $params = [],
                                           array                $listeners = []): void
    {
        $flowConfig = [
            'class' => ConfigurableDialog::class
        ];

        if ($dialogConfig instanceof IDialogConfig) {
            $flowConfig = $dialogConfig->config();
        } elseif (isset($this->flowConfigs[$dialogConfig])) {
            $flowConfig = array_merge($flowConfig, $this->flowConfigs[$dialogConfig]);
        } elseif (is_subclass_of($dialogConfig, IBotFlow::class)) {
            $flowConfig['class'] = $dialogConfig;
        } else {
            throw new RuntimeConfigurationErrorException('Unknown dialog:' . $dialogConfig);
        }

        $flowClass = $flowConfig['class'];
        unset($flowConfig['class']);

        $this->startFlow($flowClass, $telegram_user_id, $telegram_user_id, array_merge($flowConfig, $params));
    }

    /**
     * @throws RuntimeConfigurationErrorException
     * @throws \ReflectionException
     */
    private function validateFlowConfigs(array $dialogs): void
    {
        foreach ($dialogs as $dialogAlias => $dialogConfig) {
            if (!is_string($dialogAlias)) {
                throw new RuntimeConfigurationErrorException('Configuration error in bot dialogs. Alias must be a string');
            }

            if (!is_array($dialogConfig)) {
                throw new RuntimeConfigurationErrorException('Configuration error in bot dialogs. Config must be an array');
            }

            $dialogValidator = Validator::make($dialogConfig, [
                'class'               => 'string',
                'intro'               => 'required|string',
                'outro'               => 'required|string',
                'dispatchResultsWith' => 'required|string',
                'questions'           => 'required|array',
            ]);

            if ($dialogValidator->fails()) {
                throw new RuntimeConfigurationErrorException("Configuration error in bot dialog \"$dialogAlias\": " . $dialogValidator->messages()->toJson(JSON::LOG));
            } else {
                $dialogConfigSafe = $dialogValidator->safe();

                $dialogClass = $dialogConfigSafe['class'] ?? ConfigurableDialog::class;

                if (!is_subclass_of($dialogClass, IBotFlow::class)) {
                    throw new RuntimeConfigurationErrorException("Configuration error in bot dialog \"$dialogAlias\": dialog.class must exist and implement CommonDialog abstract class. Provided {$dialogAlias}.class=$dialogClass");
                }

                $dispatchResultsWith = $dialogConfigSafe['dispatchResultsWith'] ?? null;

                if (!empty($dispatchResultsWith)) {
                    $isDispatchable = in_array(
                        Dispatchable::class,
                        array_keys((new \ReflectionClass($dispatchResultsWith))->getTraits())
                    );

                    if (!$isDispatchable) {
                        throw new RuntimeConfigurationErrorException("Configuration error in bot dialog \"$dialogAlias\": dialog.dispatchResultsWith class must exist and use " . Dispatchable::class . " trait");
                    }
                }

                if (is_subclass_of($dialogClass, CommonDialog::class)) {
                    $questions = $dialogConfigSafe['questions'] ?? [];

                    foreach ($questions as $index => $question) {
                        $questionValidator = Validator::make($question, [
                            'name'          => 'string',
                            'class'         => 'string',
                            'ask'           => 'required|string',
                            'reply'         => 'string',
                            'optional'      => 'boolean',
                            'inlineOptions' => 'array',
                            'rules'         => 'array',
                        ]);

                        if ($questionValidator->fails()) {
                            throw new RuntimeConfigurationErrorException("Configuration error in bot dialog \"$dialogAlias\", question \"$index\": " . $questionValidator->messages()->toJson(JSON::LOG));
                        }

                        $questionConfigSafe = $questionValidator->safe();

                        $questionClass = $questionConfigSafe['class'] ?? ConfigurableDialogQuestion::class;

                        if ($questionClass) {
                            if (!is_subclass_of($questionClass, IBotDialogQuestion::class)) {
                                throw new RuntimeConfigurationErrorException("Configuration error in bot dialog \"$dialogAlias\", question \"$index\": Dialog question class must implement IBotDialog interface. Provided {$dialogAlias}.question.class=$questionClass");
                            }
                        }
                    }
                }
            }
        }
    }

    public function setTelegraphUser(?User $user): void
    {
        if ($user) {
            $this->user = $user;
        }
    }

    public function telegraphUser(): ?User
    {
        return $this->user ?? null;
    }
}
