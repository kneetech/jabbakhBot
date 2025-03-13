<?php

namespace Botflow\Telegraph\Models;

use Botflow\Contracts\FlowStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
 * @property User|null        $user
 */
abstract class FlowState extends Model
{

    protected $table = 'botflow_state';

    protected $casts = [
        'status'   => FlowStatus::class,
        'params'   => 'array',
        'data'     => 'array',
        'messages' => 'array',
    ];

    protected $fillable = [
        'class',
        'status',
        'monopolizing',
        'params',
        'data',
        'messages',
        'telegram_user_id',
        'telegram_chat_id',
    ];

    /**
     * @return class-string
     */
    abstract public static function userClass(): string;

    public function getParam(string $key): mixed
    {
        return data_get($this->params, $key);
    }

    public function setParam(string $key, mixed $value): void
    {
        $patch = [];
        data_set($patch, $key, $value);
        $this->params = array_replace_recursive($this->params ?: [], $patch);
    }

    public function getData(string $key): mixed
    {
        return data_get($this->data, $key);
    }

    public function setData(string $key, mixed $value): void
    {
        $patch = [];
        data_set($patch, $key, $value);
        $this->data = array_replace_recursive($this->data ?: [], $patch);
    }

    public function forgetData(string $key): void
    {
        $data = $this->data;
        data_forget($data, $key);
        $this->data = $data;
    }

    public function isQueued(): bool
    {
        return empty($this->status) || ($this->status == FlowStatus::QUEUED);
    }

    public function isActive(): bool
    {
        return $this->status == FlowStatus::ACTIVE;
    }

    public function isOk(): bool
    {
        return $this->status == FlowStatus::OK;
    }

    public function cancelled(): bool
    {
        return $this->status == FlowStatus::CANCELLED;
    }

    public function cancellationReason(): ?string
    {
        return $this->getData('cancellation.reason');
    }

    public function interrupted(): bool
    {
        return $this->status == FlowStatus::INTERRUPTED;
    }

    public function interruptionReason(): ?string
    {
        return $this->getData('interruption.reason');
    }

    public function user(): HasOne
    {
        return $this->hasOne(static::userClass(), 'telegram_id', 'telegram_user_id');
    }
}

