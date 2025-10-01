<?php

namespace LmsPro\Core;

class Request
{
    /**
     * Get the request URI path.
     *
     * @return string
     */
    public static function uri()
    {
        return trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    }

    /**
     * Get the request method.
     *
     * @return string
     */
    public static function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Get all of the input and files for the request.
     *
     * @return array
     */
    public static function all()
    {
        return $_REQUEST;
    }

    /**
     * Get a specific input item from the request.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        return $_REQUEST[$key] ?? $default;
    }

    /**
     * Check if the request contains a specific input item.
     *
     * @param string|array $key
     * @return bool
     */
    public static function has($key)
    {
        return array_key_exists($key, $_REQUEST);
    }
}