<?php

namespace Botflow\Telegraph\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;

/**
 * @property int         $id
 *
 * @property string      $name
 * @property bool|null   $gender
 * @property Carbon|null $birthday
 * @property int         $telegram_id
 * @property string|null $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $remember_token
 * @property int|null    $employee_id
 */
abstract class User extends \Illuminate\Foundation\Auth\User
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'telegram_id',
        'birthday',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'birthday'          => 'date:Y-m-d',
        ];
    }
}
