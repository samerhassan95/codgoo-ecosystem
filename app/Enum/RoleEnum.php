<?php

namespace App\Enum;

class RoleEnum
{
    public const FRONTEND_DEVELOPER = 1;
    public const BACKEND_DEVELOPER = 2;
    public const MOBILE_DEVELOPER = 3;
    public const TESTER = 4;
    public const UI_UX_DESIGNER = 5;

    /**
     * Get all available roles.
     *
     * @return array
     */
    public static function getList(): array
    {
        return [
            self::FRONTEND_DEVELOPER => 'Frontend Developer',
            self::BACKEND_DEVELOPER => 'Backend Developer',
            self::MOBILE_DEVELOPER => 'Mobile Developer',
            self::TESTER => 'Tester',
            self::UI_UX_DESIGNER => 'UI/UX Designer',
        ];
    }

    /**
     * Check if a given value is a valid role.
     *
     * @param int $value
     * @return bool
     */
    public static function isValid(int $value): bool
    {
        return array_key_exists($value, self::getList());
    }

    /**
     * Get the label for a given role.
     *
     * @param int $value
     * @return string|null
     */
    public static function getLabel(int $value): ?string
    {
        return self::getList()[$value] ?? null;
    }
}
