<?php

namespace App\Enum;

use Exception;
use Illuminate\Support\Facades\Log;
use ReflectionClassConstant;

/**
 * Class BaseEnum
 * @package App\Enum
 * @author hamada-Dev

 */
class BaseEnumeration
{

    public static function getList(): array
    {
        return [];
    }

    public static function getKeyList(): array
    {
        return array_keys(static::getList());
    }

    /**
     * @param string $value
     * @param string $constant
     * @return bool
     */
    public static function is(string $value, string $constant): bool
    {
        return self::getConstantValue($constant) === $value;
    }


    public static function getValue($val): string
    {
        return array_key_exists($val, static::getList()) ? static::getList()[$val] : '';
    }
    public static function getCode($statusWord)
    {
        // Loop through the status map and find the matching code
        foreach (static::getList() as $code => $word) {

            if (strcasecmp($word, $statusWord) === 0) {
                return $code;
            }
        }

        return null; // Return null or throw an exception if no match is found
    }


    /**
     * @param string $constant
     * @return mixed
     */
    public static function getConstantValue(string $constant)
    {
        try {
            $constant_reflex = new ReflectionClassConstant(get_called_class(), $constant);
            return $constant_reflex->getValue();
        } catch (Exception $e) {
            Log::error($e);
        }
        return null;
    }
    
}
