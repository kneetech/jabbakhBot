<?php

namespace App\Telegraph\Models;

use Botflow\Contracts\FlowStatus;
use Carbon\Carbon;

/**
 * Состояние диалога, хранимое в базе
 *
 * @property-read int         $id
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 *
 * @property string           $class
 * @property FlowStatus       $status
 * @property boolean          $monopolizing
 * @property array            $params
 * @property array            $data
 * @property array            $messages
 * @property int|null         $telegram_user_id
 * @property int|null         $telegram_chat_id
 *
 * @property User|null $user
 */
class FlowState extends \Botflow\Telegraph\Models\FlowState
{
    /**
     * @return class-string
     */
    public static function userClass(): string
    {
        return User::class;
    }
}

