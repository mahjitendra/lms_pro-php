<?php

/**
 * HTTP Response Handler Class
 * LMS Pro - Learning Management System
 */

class Response
{
    private $content = '';
    private $statusCode = 200;
    private $headers = [];
    private $cookies = [];
    private $statusTexts = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
    ];

    public function __construct($content = '', $statusCode = 200, $headers = [])
    {
        $this->setContent($content);
        $this->setStatusCode($statusCode);
        $this->setHeaders($headers);
    }

    /**
     * Set response content
     */
    public function setContent($content)
    {
        $this->content = (string) $content;
        return $this;
    }

    /**
     * Get response content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Append content to response
     */
    public function appendContent($content)
    {
        $this->content .= (string) $content;
        return $this;
    }

    /**
     * Prepend content to response
     */
    public function prependContent($content)
    {
        $this->content = (string) $content . $this->content;
        return $this;
    }

    /**
     * Set status code
     */
    public function setStatusCode($statusCode, $text = null)
    {
        $this->statusCode = (int) $statusCode;
        
        if ($text === null && isset($this->statusTexts[$statusCode])) {
            $text = $this->statusTexts[$statusCode];
        }
        
        return $this;
    }

    /**
     * Get status code
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Set header
     */
    public function setHeader($name, $value, $replace = true)
    {
        $name = $this->normalizeHeaderName($name);
        
        if ($replace || !isset($this->headers[$name])) {
            $this->headers[$name] = $value;
        } else {
            if (!is_array($this->headers[$name])) {
                $this->headers[$name] = [$this->headers[$name]];
            }
            $this->headers[$name][] = $value;
        }
        
        return $this;
    }

    /**
     * Set multiple headers
     */
    public function setHeaders($headers)
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
        return $this;
    }

    /**
     * Get header
     */
    public function getHeader($name, $default = null)
    {
        $name = $this->normalizeHeaderName($name);
        return $this->headers[$name] ?? $default;
    }

    /**
     * Get all headers
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Check if header exists
     */
    public function hasHeader($name)
    {
        $name = $this->normalizeHeaderName($name);
        return isset($this->headers[$name]);
    }

    /**
     * Remove header
     */
    public function removeHeader($name)
    {
        $name = $this->normalizeHeaderName($name);
        unset($this->headers[$name]);
        return $this;
    }

    /**
     * Normalize header name
     */
    private function normalizeHeaderName($name)
    {
        return ucwords(strtolower($name), '-');
    }

    /**
     * Set cookie
     */
    public function setCookie($name, $value = '', $options = [])
    {
        $options = array_merge([
            'expires' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => false,
            'samesite' => 'Lax'
        ], $options);
        
        $this->cookies[$name] = [
            'value' => $value,
            'options' => $options
        ];
        
        return $this;
    }

    /**
     * Get cookie
     */
    public function getCookie($name)
    {
        return $this->cookies[$name] ?? null;
    }

    /**
     * Get all cookies
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * Remove cookie
     */
    public function removeCookie($name, $path = '/', $domain = '')
    {
        $this->setCookie($name, '', [
            'expires' => time() - 3600,
            'path' => $path,
            'domain' => $domain
        ]);
        return $this;
    }

    /**
     * Set JSON response
     */
    public function json($data, $statusCode = 200, $options = 0)
    {
        $this->setHeader('Content-Type', 'application/json');
        $this->setStatusCode($statusCode);
        $this->setContent(json_encode($data, $options));
        return $this;
    }

    /**
     * Set success JSON response
     */
    public function success($message = 'Success', $data = [], $statusCode = 200)
    {
        return $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Set error JSON response
     */
    public function error($message = 'Error', $errors = [], $statusCode = 400)
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }

    /**
     * Set redirect response
     */
    public function redirect($url, $statusCode = 302)
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Location', $url);
        return $this;
    }

    /**
     * Set file download response
     */
    public function download($filePath, $filename = null, $headers = [])
    {
        if (!file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }
        
        $filename = $filename ?: basename($filePath);
        $mimeType = $this->getMimeType($filePath);
        
        $this->setHeaders(array_merge([
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => filesize($filePath),
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => 'Sat, 26 Jul 1997 05:00:00 GMT'
        ], $headers));
        
        $this->setContent(file_get_contents($filePath));
        return $this;
    }

    /**
     * Set file inline response
     */
    public function file($filePath, $headers = [])
    {
        if (!file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }
        
        $mimeType = $this->getMimeType($filePath);
        
        $this->setHeaders(array_merge([
            'Content-Type' => $mimeType,
            'Content-Length' => filesize($filePath),
        ], $headers));
        
        $this->setContent(file_get_contents($filePath));
        return $this;
    }

    /**
     * Get MIME type of file
     */
    private function getMimeType($filePath)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Set cache headers
     */
    public function cache($seconds)
    {
        $this->setHeader('Cache-Control', 'public, max-age=' . $seconds);
        $this->setHeader('Expires', gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT');
        return $this;
    }

    /**
     * Set no-cache headers
     */
    public function noCache()
    {
        $this->setHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
        return $this;
    }

    /**
     * Set ETag header
     */
    public function etag($etag, $weak = false)
    {
        $etag = $weak ? 'W/"' . $etag . '"' : '"' . $etag . '"';
        $this->setHeader('ETag', $etag);
        return $this;
    }

    /**
     * Set Last-Modified header
     */
    public function lastModified($time)
    {
        if (is_int($time)) {
            $time = gmdate('D, d M Y H:i:s', $time) . ' GMT';
        }
        $this->setHeader('Last-Modified', $time);
        return $this;
    }

    /**
     * Send response headers
     */
    public function sendHeaders()
    {
        if (headers_sent()) {
            return $this;
        }
        
        // Send status line
        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
        $statusText = $this->statusTexts[$this->statusCode] ?? 'Unknown';
        header("{$protocol} {$this->statusCode} {$statusText}");
        
        // Send headers
        foreach ($this->headers as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    header("{$name}: {$v}", false);
                }
            } else {
                header("{$name}: {$value}");
            }
        }
        
        // Send cookies
        foreach ($this->cookies as $name => $cookie) {
            $options = $cookie['options'];
            
            if (PHP_VERSION_ID >= 70300) {
                setcookie($name, $cookie['value'], $options);
            } else {
                setcookie(
                    $name,
                    $cookie['value'],
                    $options['expires'],
                    $options['path'],
                    $options['domain'],
                    $options['secure'],
                    $options['httponly']
                );
            }
        }
        
        return $this;
    }

    /**
     * Send response content
     */
    public function sendContent()
    {
        echo $this->content;
        return $this;
    }

    /**
     * Send complete response
     */
    public function send()
    {
        $this->sendHeaders();
        $this->sendContent();
        
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        }
        
        return $this;
    }

    /**
     * Check if response is informational
     */
    public function isInformational()
    {
        return $this->statusCode >= 100 && $this->statusCode < 200;
    }

    /**
     * Check if response is successful
     */
    public function isSuccessful()
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Check if response is redirection
     */
    public function isRedirection()
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * Check if response is client error
     */
    public function isClientError()
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Check if response is server error
     */
    public function isServerError()
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * Check if response is OK
     */
    public function isOk()
    {
        return $this->statusCode === 200;
    }

    /**
     * Check if response is forbidden
     */
    public function isForbidden()
    {
        return $this->statusCode === 403;
    }

    /**
     * Check if response is not found
     */
    public function isNotFound()
    {
        return $this->statusCode === 404;
    }

    /**
     * Check if response is empty
     */
    public function isEmpty()
    {
        return in_array($this->statusCode, [204, 304]);
    }

    /**
     * Get response as string
     */
    public function __toString()
    {
        return $this->content;
    }
}