<?php

namespace App\Enum;

class SectionEnum
{
    public const EnvironmentInstallation = 1;
    public const SetupCodgoo     = 2;
    public const Privacy           = 3;

    /**
     * Get the list of all sections with their labels.
     *
     * @return array
     */
    public static function getList(): array
    {
        return [
            self::EnvironmentInstallation => 'EnvironmentInstallation',
            self::SetupCodgoo     => 'SetupCodgoo',
            self::Privacy           => 'Privacy',
        ];
    }

    /**
     * Check if a given value is a valid section.
     *
     * @param int $value
     * @return bool
     */
    public static function isValid(int $value): bool
    {
        return array_key_exists($value, self::getList());
    }

    /**
     * Get the label for a given section.
     *
     * @param int $value
     * @return string|null
     */
    public static function getLabel(int $value): ?string
    {
        return self::getList()[$value] ?? null;
    }
}
