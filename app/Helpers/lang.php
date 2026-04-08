<?php

/**
 * Language helper functions
 * This file is required by the autoloader
 */

if (!function_exists('lang')) {
    /**
     * Get language string
     * 
     * @param string $key
     * @param array $replace
     * @param string $locale
     * @return string
     */
    function lang($key, $replace = [], $locale = null)
    {
        return __($key, $replace, $locale);
    }
}

if (!function_exists('trans_choice')) {
    /**
     * Get the translation for a given key with pluralization
     * 
     * @param string $key
     * @param int $number
     * @param array $replace
     * @param string $locale
     * @return string
     */
    function trans_choice($key, $number, $replace = [], $locale = null)
    {
        return trans_choice($key, $number, $replace, $locale);
    }
}