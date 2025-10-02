<?php

/**
 * Helper Functions Class
 * LMS Pro - Learning Management System
 */

class Helper
{
    /**
     * Generate a random string
     */
    public static function randomString($length = 10, $characters = null)
    {
        if ($characters === null) {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        
        $charactersLength = strlen($characters);
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        
        return $randomString;
    }

    /**
     * Generate UUID v4
     */
    public static function uuid()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Sanitize string for URL slug
     */
    public static function slug($string, $separator = '-')
    {
        $string = strtolower(trim($string));
        $string = preg_replace('/[^a-z0-9-]/', $separator, $string);
        $string = preg_replace('/-+/', $separator, $string);
        return trim($string, $separator);
    }

    /**
     * Truncate text
     */
    public static function truncate($text, $length = 100, $suffix = '...')
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        return mb_substr($text, 0, $length) . $suffix;
    }

    /**
     * Format file size
     */
    public static function formatFileSize($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Format currency
     */
    public static function formatCurrency($amount, $currency = 'USD', $locale = 'en_US')
    {
        if (class_exists('NumberFormatter')) {
            $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            return $formatter->formatCurrency($amount, $currency);
        }
        
        // Fallback formatting
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
        ];
        
        $symbol = $symbols[$currency] ?? $currency . ' ';
        return $symbol . number_format($amount, 2);
    }

    /**
     * Format date
     */
    public static function formatDate($date, $format = 'M d, Y')
    {
        if (!$date) {
            return '';
        }
        
        if (is_string($date)) {
            $date = strtotime($date);
        }
        
        return date($format, $date);
    }

    /**
     * Time ago format
     */
    public static function timeAgo($datetime)
    {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) {
            return 'just now';
        }
        
        $condition = [
            12 * 30 * 24 * 60 * 60 => 'year',
            30 * 24 * 60 * 60 => 'month',
            24 * 60 * 60 => 'day',
            60 * 60 => 'hour',
            60 => 'minute'
        ];
        
        foreach ($condition as $secs => $str) {
            $d = $time / $secs;
            
            if ($d >= 1) {
                $t = round($d);
                return $t . ' ' . $str . ($t > 1 ? 's' : '') . ' ago';
            }
        }
        
        return 'just now';
    }

    /**
     * Escape HTML
     */
    public static function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Clean HTML (remove tags)
     */
    public static function cleanHtml($string)
    {
        return strip_tags($string);
    }

    /**
     * Sanitize HTML (allow safe tags)
     */
    public static function sanitizeHtml($string, $allowedTags = '<p><br><strong><em><u><a><ul><ol><li>')
    {
        return strip_tags($string, $allowedTags);
    }

    /**
     * Generate excerpt from text
     */
    public static function excerpt($text, $length = 150, $suffix = '...')
    {
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        $text = mb_substr($text, 0, $length);
        $lastSpace = mb_strrpos($text, ' ');
        
        if ($lastSpace !== false) {
            $text = mb_substr($text, 0, $lastSpace);
        }
        
        return $text . $suffix;
    }

    /**
     * Convert string to camelCase
     */
    public static function camelCase($string)
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string))));
    }

    /**
     * Convert string to PascalCase
     */
    public static function pascalCase($string)
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string)));
    }

    /**
     * Convert string to snake_case
     */
    public static function snakeCase($string)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
    }

    /**
     * Convert string to kebab-case
     */
    public static function kebabCase($string)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $string));
    }

    /**
     * Check if string starts with
     */
    public static function startsWith($haystack, $needle)
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    /**
     * Check if string ends with
     */
    public static function endsWith($haystack, $needle)
    {
        return substr($haystack, -strlen($needle)) === $needle;
    }

    /**
     * Check if string contains
     */
    public static function contains($haystack, $needle)
    {
        return strpos($haystack, $needle) !== false;
    }

    /**
     * Generate gravatar URL
     */
    public static function gravatar($email, $size = 80, $default = 'mp')
    {
        $hash = md5(strtolower(trim($email)));
        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d={$default}";
    }

    /**
     * Validate email
     */
    public static function isValidEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL
     */
    public static function isValidUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate IP address
     */
    public static function isValidIp($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Get client IP address
     */
    public static function getClientIp()
    {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get user agent
     */
    public static function getUserAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Check if request is mobile
     */
    public static function isMobile()
    {
        $userAgent = self::getUserAgent();
        return preg_match('/(android|iphone|ipad|mobile)/i', $userAgent);
    }

    /**
     * Generate QR code URL
     */
    public static function qrCode($data, $size = 200)
    {
        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($data);
    }

    /**
     * Convert array to XML
     */
    public static function arrayToXml($array, $rootElement = 'root', $xml = null)
    {
        if ($xml === null) {
            $xml = new SimpleXMLElement("<{$rootElement}></{$rootElement}>");
        }
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                self::arrayToXml($value, $key, $xml->addChild($key));
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
        
        return $xml->asXML();
    }

    /**
     * Convert XML to array
     */
    public static function xmlToArray($xmlString)
    {
        $xml = simplexml_load_string($xmlString);
        return json_decode(json_encode($xml), true);
    }

    /**
     * Flatten array
     */
    public static function flattenArray($array, $prefix = '')
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? $key : $prefix . '.' . $key;
            
            if (is_array($value)) {
                $result = array_merge($result, self::flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        
        return $result;
    }

    /**
     * Get nested array value using dot notation
     */
    public static function arrayGet($array, $key, $default = null)
    {
        if (isset($array[$key])) {
            return $array[$key];
        }
        
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            
            $array = $array[$segment];
        }
        
        return $array;
    }

    /**
     * Set nested array value using dot notation
     */
    public static function arraySet(&$array, $key, $value)
    {
        $keys = explode('.', $key);
        
        while (count($keys) > 1) {
            $key = array_shift($keys);
            
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            
            $array = &$array[$key];
        }
        
        $array[array_shift($keys)] = $value;
    }

    /**
     * Remove empty values from array
     */
    public static function arrayClean($array)
    {
        return array_filter($array, function($value) {
            return !empty($value) || $value === 0 || $value === '0';
        });
    }

    /**
     * Generate breadcrumb from path
     */
    public static function breadcrumb($path, $separator = '/')
    {
        $parts = explode($separator, trim($path, $separator));
        $breadcrumb = [];
        $currentPath = '';
        
        foreach ($parts as $part) {
            $currentPath .= $separator . $part;
            $breadcrumb[] = [
                'title' => ucwords(str_replace(['-', '_'], ' ', $part)),
                'url' => $currentPath
            ];
        }
        
        return $breadcrumb;
    }

    /**
     * Log message to file
     */
    public static function log($message, $level = 'info', $file = 'app.log')
    {
        $logFile = LOG_PATH . '/' . $file;
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$level}: {$message}\n";
        
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Send email (basic implementation)
     */
    public static function sendEmail($to, $subject, $message, $headers = [])
    {
        $defaultHeaders = [
            'From' => 'noreply@lmspro.com',
            'Reply-To' => 'noreply@lmspro.com',
            'Content-Type' => 'text/html; charset=UTF-8'
        ];
        
        $headers = array_merge($defaultHeaders, $headers);
        $headerString = '';
        
        foreach ($headers as $key => $value) {
            $headerString .= "{$key}: {$value}\r\n";
        }
        
        return mail($to, $subject, $message, $headerString);
    }

    /**
     * Generate secure hash
     */
    public static function hash($data, $algorithm = 'sha256')
    {
        return hash($algorithm, $data);
    }

    /**
     * Generate HMAC
     */
    public static function hmac($data, $key, $algorithm = 'sha256')
    {
        return hash_hmac($algorithm, $data, $key);
    }

    /**
     * Encrypt data
     */
    public static function encrypt($data, $key = null)
    {
        $key = $key ?: $_ENV['ENCRYPTION_KEY'] ?? 'default-key';
        $cipher = 'AES-256-CBC';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
        $encrypted = openssl_encrypt($data, $cipher, $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt data
     */
    public static function decrypt($data, $key = null)
    {
        $key = $key ?: $_ENV['ENCRYPTION_KEY'] ?? 'default-key';
        $cipher = 'AES-256-CBC';
        $data = base64_decode($data);
        $ivLength = openssl_cipher_iv_length($cipher);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        
        return openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
    }

    /**
     * Create directory if it doesn't exist
     */
    public static function ensureDirectory($path, $permissions = 0755)
    {
        if (!is_dir($path)) {
            return mkdir($path, $permissions, true);
        }
        
        return true;
    }

    /**
     * Delete directory recursively
     */
    public static function deleteDirectory($path)
    {
        if (!is_dir($path)) {
            return false;
        }
        
        $files = array_diff(scandir($path), ['.', '..']);
        
        foreach ($files as $file) {
            $filePath = $path . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($filePath)) {
                self::deleteDirectory($filePath);
            } else {
                unlink($filePath);
            }
        }
        
        return rmdir($path);
    }

    /**
     * Copy directory recursively
     */
    public static function copyDirectory($source, $destination)
    {
        if (!is_dir($source)) {
            return false;
        }
        
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $files = array_diff(scandir($source), ['.', '..']);
        
        foreach ($files as $file) {
            $sourcePath = $source . DIRECTORY_SEPARATOR . $file;
            $destPath = $destination . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($sourcePath)) {
                self::copyDirectory($sourcePath, $destPath);
            } else {
                copy($sourcePath, $destPath);
            }
        }
        
        return true;
    }

    /**
     * Get file extension
     */
    public static function getFileExtension($filename)
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * Get MIME type from file extension
     */
    public static function getMimeType($filename)
    {
        $extension = self::getFileExtension($filename);
        
        $mimeTypes = [
            'txt' => 'text/plain',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'mp4' => 'video/mp4',
            'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}