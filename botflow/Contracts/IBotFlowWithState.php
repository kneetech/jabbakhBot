<?php

namespace Botflow\Contracts;

use Botflow\Telegraph\Models\FlowState;

interface IBotFlowWithState extends IBotFlow
{
    /**
     * @return class-string<FlowState>
     */
    public static function stateClass(): string;
    public function interrupt(int $code, ?string $reason = null): void;
}
