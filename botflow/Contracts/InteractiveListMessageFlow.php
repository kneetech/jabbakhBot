<?php

namespace Botflow\Contracts;

use Botflow\Enums\Emoji;
use Botflow\Exceptions\Exception;
use Botflow\Exceptions\RuntimeUnexpectedErrorException;

abstract class InteractiveListMessageFlow extends InteractiveMessageFlow
{

    protected int $page = 1;

    protected int $perPage = 10;

    public function __construct(IBotService $botService, array $params = [])
    {
        parent::__construct($botService, $params);

        if ($page = $this->state->getData('page')) {
            $this->page = $page;
        }

        if ($perPage = $this->state->getParam('perPage')) {
            $this->page = $perPage;
        }

        $this->page = $this->state()->getData('page') ?: 1;
    }

    public function start(): void
    {
        $this->page = 1;

        parent::start();
    }

    public abstract function count(): int;

    public abstract function list(int $page): array;

    /**
     * @param array $item
     * @throws Exception
     */
    public abstract function listItemToButton($item): ?InteractiveMessageButton;

    public abstract function extraButtons(): array;

    /**
     * @inheritDoc
     */
    public function buttons(): array
    {
        $list = $this->list($this->page);

        $buttons = [];
        foreach ($list as $item) {
            $button = $this->listItemToButton($item);
            if ($button) {
                $row = [$button];
                $buttons[] = $row;
            }
        }

        $count = $this->count();
        $paginationButtonsRow = [];
        if ($this->page > 1) {
            $paginationButtonsRow[] = new InteractiveMessageButton(
                Emoji::LEFT_POINTING_DOUBLE_TRIANGLE->value. ' предыдущая страница',
                ['page' => $this->page - 1]
            );
        }


        if ($count > $this->page * $this->perPage) {
            $paginationButtonsRow[] = new InteractiveMessageButton(
                Emoji::RIGHT_POINTING_DOUBLE_TRIANGLE->value . ' следующая страница',
                ['page' => $this->page + 1]
            );
        }

        if (!empty($paginationButtonsRow)) {
            $buttons[] = $paginationButtonsRow;
        }

        $extraButtons = $this->extraButtons();
        foreach ($extraButtons as $extraButtonRow) {
            $buttons[] = $extraButtonRow;
        }

        return $buttons;
    }

    public function outro(): ?string
    {
        return null;
    }

    /**
     * @throws RuntimeUnexpectedErrorException
     */
    public function callback($params): void
    {
        if (isset($params['page'])) {
            $this->page = $params['page'];
            $this->store();
        }
    }

    public function store(): void
    {
        $this->state->setData('page', $this->page);

        parent::store();
    }
}
