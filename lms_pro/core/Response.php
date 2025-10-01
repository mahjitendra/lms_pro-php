<?php

namespace LmsPro\Core;

class Response
{
    /**
     * The HTTP status code.
     *
     * @var int
     */
    protected $statusCode = 200;

    /**
     * The response headers.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Set the HTTP status code.
     *
     * @param int $code
     * @return self
     */
    public function setStatusCode(int $code)
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Add a header to the response.
     *
     * @param string $name
     * @param string $value
     * @return self
     */
    public function withHeader(string $name, string $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Send a JSON response.
     *
     * @param mixed $data
     * @param int $status
     */
    public function json($data, int $status = 200)
    {
        $this->setStatusCode($status)
             ->withHeader('Content-Type', 'application/json');

        $this->sendHeaders();
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to a new URL.
     *
     * @param string $url
     * @param int $status
     */
    public function redirect(string $url, int $status = 302)
    {
        $this->setStatusCode($status)
             ->withHeader('Location', $url);

        $this->sendHeaders();
        exit;
    }

    /**
     * Send the response headers.
     */
    public function sendHeaders()
    {
        if (headers_sent()) {
            return;
        }

        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
    }
}