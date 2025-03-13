<?php

namespace Botflow\Traits;

use Carbon\Carbon;
use Illuminate\Support\Str;

trait HasWorkSchedule
{
    protected static function workStartHour(): int
    {
        return config('bot.first_work_hour', 9);
    }

    protected static function workEndHour(): int
    {
        return config('bot.last_work_hour', 18);
    }

    protected static function workStartTime(): string
    {
        return self::twoDigits(self::workStartHour()) . ':00:00';
    }

    protected static function workEndTime(): string
    {
        return self::twoDigits(self::workEndHour()) . ':00:00';
    }

    protected static function twoDigits(int $number): string
    {
        return Str::padLeft($number,2, '0');
    }

    protected static function nearestWorkSecond(Carbon $datetime): ?Carbon
    {
        if (
            (clone $datetime)->hour < self::workStartHour()
            && (clone $datetime)->isWeekday()
        ) {
            return (clone $datetime)->setTimeFromTimeString(self::workStartTime());
        }
        if (
            (clone $datetime)->hour >= self::workEndHour()
            || (clone $datetime)->isWeekend()
        ) {
            return (clone $datetime)->nextWeekday()->setTimeFromTimeString(self::workStartTime());
        }
        return null;
    }

    protected static function nearestWorkSecondAfterDelay(Carbon $start, int $minutes): Carbon
    {
        $later = (clone $start)->addMinutes($minutes);
        $nearestWorkSecondFromLater = self::nearestWorkSecond($later);
        if (is_null($nearestWorkSecondFromLater)) {
            return $later;
        }
        return $nearestWorkSecondFromLater;
    }
}
