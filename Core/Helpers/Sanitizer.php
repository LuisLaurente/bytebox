<?php

namespace Core\Helpers;

class Sanitizer
{
    public static function cleanString($string)
    {
        return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
    }

    public static function stripTags($string)
    {
        return strip_tags($string);
    }

    public static function sanitizeEmail($email)
    {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
}
