<?php

if (!function_exists('view')) {
    /**
     * Render a view.
     *
     * @param string $view
     * @param array $data
     */
    function view(string $view, array $data = [])
    {
        (new LmsPro\Core\Controller())->view($view, $data);
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to a given URL.
     *
     * @param string $url
     */
    function redirect(string $url)
    {
        (new LmsPro\Core\Response())->redirect($url);
    }
}

if (!function_exists('json')) {
    /**
     * Return a JSON response.
     *
     * @param mixed $data
     * @param int $status
     */
    function json($data, int $status = 200)
    {
        (new LmsPro\Core\Response())->json($data, $status);
    }
}