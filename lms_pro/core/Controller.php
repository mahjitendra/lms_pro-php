<?php

namespace LmsPro\Core;

use Exception;

abstract class Controller
{
    /**
     * The response instance.
     *
     * @var Response
     */
    protected $response;

    public function __construct()
    {
        $this->response = new Response();
    }

    /**
     * Render a view file with data.
     *
     * @param string $view The view file path (e.g., 'auth.login').
     * @param array $data Data to be extracted and made available to the view.
     * @throws Exception if the view file is not found.
     */
    protected function view(string $view, array $data = [])
    {
        // Convert dot notation to directory separators
        $viewPath = LMS_PRO_ROOT . '/lms_pro/views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            throw new Exception("View file not found: {$viewPath}");
        }

        // Make the data array available as variables in the view
        extract($data);

        // Start output buffering to capture the view's output
        ob_start();

        // Include the view file
        require $viewPath;

        // Get the contents of the buffer and clean it
        $content = ob_get_clean();

        // Send the content to the browser
        echo $content;
    }

    /**
     * Redirect to a given URL.
     *
     * @param string $url
     */
    protected function redirect(string $url)
    {
        $this->response->redirect($url);
    }

    /**
     * Return a JSON response.
     *
     * @param mixed $data
     * @param int $status
     */
    protected function json($data, int $status = 200)
    {
        $this->response->json($data, $status);
    }
}