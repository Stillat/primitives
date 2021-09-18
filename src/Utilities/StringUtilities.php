<?php

namespace Stillat\Primitives\Utilities;

class StringUtilities
{
    /**
     * Determine if a given string ends with a given substring.
     *
     * @param  string  $haystack
     * @param  string|string[]  $needles
     * @return bool
     */
    public static function endsWith($haystack, $needles)
    {
        // From: https://github.com/laravel/framework/blob/8.x/src/Illuminate/Support/Str.php#L217-L229
        foreach ((array) $needles as $needle) {
            if (
                $needle !== '' && $needle !== null
                && substr($haystack, -strlen($needle)) === (string) $needle
            ) {
                return true;
            }
        }

        return false;
    }
}
