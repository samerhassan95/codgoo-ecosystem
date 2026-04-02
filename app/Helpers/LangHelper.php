<?php

if (!function_exists('localized_text')) {
    function localized_text($text)
    {
        if (!$text) return null;

        $lang = request()->header('Accept-Language', 'en');

        if (!str_contains($text, '|')) {
            return $text; // fallback if not multilingual
        }

        [$en, $ar] = array_map('trim', explode('|', $text, 2));

        return $lang === 'ar' ? $ar : $en;
    }
}
