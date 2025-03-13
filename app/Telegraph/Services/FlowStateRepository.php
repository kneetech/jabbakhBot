<?php

namespace App\Telegraph\Services;

use App\Telegraph\Models\FlowState;

class FlowStateRepository extends \Botflow\Telegraph\Services\FlowStateRepository
{
    /**
     * @return class-string<FlowState>
     */
    public static function stateClass(): string
    {
        return FlowState::class;
    }
}
