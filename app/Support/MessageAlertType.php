<?php

namespace App\Support;

final class MessageAlertType
{
    public const CLIENT = 'client';

    public const EMPLOYEE = 'employee';

    /**
     * @return self::*
     */
    public static function normalize(mixed $value): string
    {
        $t = strtolower(trim((string) ($value ?? self::CLIENT)));

        return in_array($t, [self::CLIENT, self::EMPLOYEE], true) ? $t : self::CLIENT;
    }
}
