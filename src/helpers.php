<?php

if (!function_exists('config')) {
    /**
     * Used as a replacement to Laravel's config() which is not available in Flarum
     * We are going to always use the defaults or re-implement functions but Scout will still call this method sometimes
     * This is the same implementation used in kilowhat/flarum-ext-formulaire
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function config(string $key, $default = null)
    {
        return $default;
    }
}
