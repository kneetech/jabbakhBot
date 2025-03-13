<?php

namespace App\Telegraph\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @method static UserFactory factory($count = null, $state = [])
 */
class User extends \Botflow\Telegraph\Models\User
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'telegram_id'
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => UserRole::class,
            'birthday'          => 'date:Y-m-d',
        ];
    }

    protected static function newFactory(): UserFactory
    {
        return new UserFactory();
    }
}
