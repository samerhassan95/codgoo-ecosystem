<?php

namespace App\Enum;

class SettingStatus extends BaseEnumeration
{

    public const SETTING_STATUS_PENDING  = '1';
    public const SETTING_STATUS_ACTIVE   = '2';
    public const SETTING_STATUS_DISABLED = '3';

    /**
     * @param string $value
     * @return array
     */
    public static function getList(string $value = ''): array
    {
        $enumerationTranslation = 'site.general_setting_';
        return [
            self::SETTING_STATUS_PENDING => __($enumerationTranslation . self::SETTING_STATUS_PENDING),
            self::SETTING_STATUS_ACTIVE => __($enumerationTranslation . self::SETTING_STATUS_ACTIVE),
            self::SETTING_STATUS_DISABLED => __($enumerationTranslation . self::SETTING_STATUS_DISABLED),
        ];
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function isPending(string $value): bool
    {
        return self::is($value, 'SETTING_STATUS_PENDING');
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function isActive(string $value): bool
    {
        return self::is($value, 'SETTING_STATUS_ACTIVE');
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function isDisabled(string $value): bool
    {
        return self::is($value, 'SETTING_STATUS_DISABLED');
    }

    /**
     * @return string
     */
    public static function getActive(): string
    {
        return self::SETTING_STATUS_ACTIVE;
    }

    /**
     * @return string
     */
    public static function getPending(): string
    {
        return self::SETTING_STATUS_PENDING;
    }

    /**
     * @return string
     */
    public static function getDisabled(): string
    {
        return self::SETTING_STATUS_DISABLED;
    }
}
