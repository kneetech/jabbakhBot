<?php

namespace App\Enums;

use Botflow\Enums\EnumToArray;

enum UserRole: string
{
    use EnumToArray;

    /** создатель */
    case Root = "root";

    /** администратор */
    case Admin = "admin";

    /** сотрудник */
    case Employee = "employee";

    /** неизвестный гражданин или гражданка */
    case Unknown = "unknown";

    public function label(): string
    {
        return match ($this) {
            self::Root => 'разработчик',
            self::Admin => 'администратор',
            self::Employee => 'исполнитель (ответственный)',
            self::Unknown => 'неизвестный гражданин или гражданка'
        };
    }

    public function isSupervisor(): bool
    {
        return match ($this) {
            self::Root, self::Admin => true,
            self::Employee, self::Unknown => false
        };
    }

    public function isAdmin(): bool
    {
        return match ($this) {
            self::Root, self::Admin => true,
            self::Employee, self::Unknown => false
        };
    }
}
