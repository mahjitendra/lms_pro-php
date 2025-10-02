<?php

/**
 * View Handler Class
 * LMS Pro - Learning Management System
 */

class View
{
    private $viewPath;
    private $layoutPath;
    private $data = [];
    private $sections = [];
    private $currentSection = null;
    private $extends = null;

    public function __construct()
    {
        $this->viewPath = __DIR__ . '/../views';
        $this->layoutPath = __DIR__ . '/../views/layouts';
    }

    /**
     * Render a view
     */
    public function render($view, $data = [], $layout = null)
    {
        $this->data = array_merge($this->data, $data);
        
        // Start output buffering
        ob_start();
        
        try {
            // Include the view file
            $this->includeView($view);
            
            // Get the content
            $content = ob_get_clean();
            
            // If layout is specified or extended, wrap content in layout
            if ($layout || $this->extends) {
                $layoutName = $layout ?: $this->extends;
                $this->data['content'] = $content;
                
                // Merge sections into data
                $this->data = array_merge($this->data, $this->sections);
                
                ob_start();
                $this->includeLayout($layoutName);
                $content = ob_get_clean();
            }
            
            return $content;
            
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * Include a view file
     */
    private function includeView($view)
    {
        $viewFile = $this->getViewPath($view);
        
        if (!file_exists($viewFile)) {
            throw new Exception("View file not found: {$view}");
        }
        
        // Extract data to variables
        extract($this->data);
        
        // Include the view file
        include $viewFile;
    }

    /**
     * Include a layout file
     */
    private function includeLayout($layout)
    {
        $layoutFile = $this->getLayoutPath($layout);
        
        if (!file_exists($layoutFile)) {
            throw new Exception("Layout file not found: {$layout}");
        }
        
        // Extract data to variables
        extract($this->data);
        
        // Include the layout file
        include $layoutFile;
    }

    /**
     * Get view file path
     */
    private function getViewPath($view)
    {
        return $this->viewPath . '/' . str_replace('.', '/', $view) . '.php';
    }

    /**
     * Get layout file path
     */
    private function getLayoutPath($layout)
    {
        return $this->layoutPath . '/' . $layout . '.php';
    }

    /**
     * Extend a layout
     */
    public function extend($layout)
    {
        $this->extends = $layout;
    }

    /**
     * Start a section
     */
    public function section($name)
    {
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * End current section
     */
    public function endSection()
    {
        if ($this->currentSection) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = null;
        }
    }

    /**
     * Yield section content
     */
    public function yield($section, $default = '')
    {
        return $this->sections[$section] ?? $default;
    }

    /**
     * Include a partial view
     */
    public function partial($view, $data = [])
    {
        $originalData = $this->data;
        $this->data = array_merge($this->data, $data);
        
        $this->includeView($view);
        
        $this->data = $originalData;
    }

    /**
     * Include a component
     */
    public function component($component, $data = [])
    {
        $this->partial("components.{$component}", $data);
    }

    /**
     * Escape HTML
     */
    public function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate URL
     */
    public function url($path = '')
    {
        return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Generate asset URL
     */
    public function asset($path)
    {
        return $this->url('assets/' . ltrim($path, '/'));
    }

    /**
     * Generate upload URL
     */
    public function upload($path)
    {
        return $this->url('uploads/' . ltrim($path, '/'));
    }

    /**
     * Get old input value
     */
    public function old($key, $default = '')
    {
        $session = App::getInstance()->getSession();
        $oldInput = $session->getFlash('old_input', []);
        return $oldInput[$key] ?? $default;
    }

    /**
     * Get error message
     */
    public function error($key)
    {
        $session = App::getInstance()->getSession();
        $errors = $session->getFlash('errors', []);
        return $errors[$key] ?? null;
    }

    /**
     * Check if there are errors
     */
    public function hasErrors()
    {
        $session = App::getInstance()->getSession();
        $errors = $session->getFlash('errors', []);
        return !empty($errors);
    }

    /**
     * Get flash message
     */
    public function flash($type)
    {
        $session = App::getInstance()->getSession();
        return $session->getFlash($type);
    }

    /**
     * Get current user
     */
    public function user()
    {
        $auth = App::getInstance()->get('auth');
        return $auth->user();
    }

    /**
     * Check if user is authenticated
     */
    public function auth()
    {
        $auth = App::getInstance()->get('auth');
        return $auth->check();
    }

    /**
     * Check if user has role
     */
    public function hasRole($role)
    {
        $auth = App::getInstance()->get('auth');
        return $auth->hasRole($role);
    }

    /**
     * Check if user has permission
     */
    public function hasPermission($permission)
    {
        $auth = App::getInstance()->get('auth');
        return $auth->hasPermission($permission);
    }

    /**
     * Generate CSRF token input
     */
    public function csrf()
    {
        $session = App::getInstance()->getSession();
        $token = $session->get('csrf_token');
        
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            $session->set('csrf_token', $token);
        }
        
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Format date
     */
    public function date($date, $format = 'M d, Y')
    {
        if (!$date) return '';
        return date($format, strtotime($date));
    }

    /**
     * Format currency
     */
    public function currency($amount, $currency = 'USD')
    {
        return '$' . number_format($amount, 2);
    }

    /**
     * Truncate text
     */
    public function truncate($text, $length = 100, $suffix = '...')
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . $suffix;
    }

    /**
     * Format file size
     */
    public function fileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Generate pagination links
     */
    public function paginate($pagination, $url = null)
    {
        if (!$url) {
            $request = App::getInstance()->getRequest();
            $url = $request->getUri();
        }
        
        $html = '<nav aria-label="Pagination">';
        $html .= '<ul class="pagination">';
        
        // Previous page
        if ($pagination['has_prev']) {
            $prevUrl = $this->addQueryParam($url, 'page', $pagination['prev_page']);
            $html .= '<li class="page-item"><a class="page-link" href="' . $prevUrl . '">Previous</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
        }
        
        // Page numbers
        $start = max(1, $pagination['current_page'] - 2);
        $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
        
        for ($i = $start; $i <= $end; $i++) {
            $pageUrl = $this->addQueryParam($url, 'page', $i);
            $active = $i == $pagination['current_page'] ? ' active' : '';
            $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $pageUrl . '">' . $i . '</a></li>';
        }
        
        // Next page
        if ($pagination['has_next']) {
            $nextUrl = $this->addQueryParam($url, 'page', $pagination['next_page']);
            $html .= '<li class="page-item"><a class="page-link" href="' . $nextUrl . '">Next</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
        }
        
        $html .= '</ul>';
        $html .= '</nav>';
        
        return $html;
    }

    /**
     * Add query parameter to URL
     */
    private function addQueryParam($url, $param, $value)
    {
        $parsed = parse_url($url);
        $query = [];
        
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query);
        }
        
        $query[$param] = $value;
        
        $newUrl = $parsed['scheme'] . '://' . $parsed['host'];
        if (isset($parsed['port'])) {
            $newUrl .= ':' . $parsed['port'];
        }
        $newUrl .= $parsed['path'] ?? '/';
        $newUrl .= '?' . http_build_query($query);
        
        return $newUrl;
    }

    /**
     * Generate breadcrumbs
     */
    public function breadcrumbs($items)
    {
        $html = '<nav aria-label="breadcrumb">';
        $html .= '<ol class="breadcrumb">';
        
        $count = count($items);
        foreach ($items as $i => $item) {
            $isLast = ($i == $count - 1);
            
            if ($isLast) {
                $html .= '<li class="breadcrumb-item active" aria-current="page">' . $this->escape($item['title']) . '</li>';
            } else {
                $html .= '<li class="breadcrumb-item"><a href="' . $item['url'] . '">' . $this->escape($item['title']) . '</a></li>';
            }
        }
        
        $html .= '</ol>';
        $html .= '</nav>';
        
        return $html;
    }

    /**
     * Generate alert message
     */
    public function alert($message, $type = 'info', $dismissible = true)
    {
        $dismissClass = $dismissible ? ' alert-dismissible fade show' : '';
        $html = '<div class="alert alert-' . $type . $dismissClass . '" role="alert">';
        $html .= $this->escape($message);
        
        if ($dismissible) {
            $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Generate form input
     */
    public function input($name, $type = 'text', $attributes = [])
    {
        $value = $this->old($name, $attributes['value'] ?? '');
        $class = 'form-control';
        
        if ($this->error($name)) {
            $class .= ' is-invalid';
        }
        
        $attributes['class'] = $attributes['class'] ?? $class;
        $attributes['name'] = $name;
        $attributes['type'] = $type;
        $attributes['value'] = $value;
        
        $html = '<input';
        foreach ($attributes as $key => $val) {
            $html .= ' ' . $key . '="' . $this->escape($val) . '"';
        }
        $html .= '>';
        
        if ($error = $this->error($name)) {
            $html .= '<div class="invalid-feedback">' . $this->escape($error) . '</div>';
        }
        
        return $html;
    }

    /**
     * Generate textarea
     */
    public function textarea($name, $attributes = [])
    {
        $value = $this->old($name, $attributes['value'] ?? '');
        $class = 'form-control';
        
        if ($this->error($name)) {
            $class .= ' is-invalid';
        }
        
        $attributes['class'] = $attributes['class'] ?? $class;
        $attributes['name'] = $name;
        
        unset($attributes['value']);
        
        $html = '<textarea';
        foreach ($attributes as $key => $val) {
            $html .= ' ' . $key . '="' . $this->escape($val) . '"';
        }
        $html .= '>' . $this->escape($value) . '</textarea>';
        
        if ($error = $this->error($name)) {
            $html .= '<div class="invalid-feedback">' . $this->escape($error) . '</div>';
        }
        
        return $html;
    }

    /**
     * Generate select dropdown
     */
    public function select($name, $options = [], $attributes = [])
    {
        $selected = $this->old($name, $attributes['value'] ?? '');
        $class = 'form-select';
        
        if ($this->error($name)) {
            $class .= ' is-invalid';
        }
        
        $attributes['class'] = $attributes['class'] ?? $class;
        $attributes['name'] = $name;
        
        unset($attributes['value']);
        
        $html = '<select';
        foreach ($attributes as $key => $val) {
            $html .= ' ' . $key . '="' . $this->escape($val) . '"';
        }
        $html .= '>';
        
        foreach ($options as $value => $label) {
            $selectedAttr = ($value == $selected) ? ' selected' : '';
            $html .= '<option value="' . $this->escape($value) . '"' . $selectedAttr . '>' . $this->escape($label) . '</option>';
        }
        
        $html .= '</select>';
        
        if ($error = $this->error($name)) {
            $html .= '<div class="invalid-feedback">' . $this->escape($error) . '</div>';
        }
        
        return $html;
    }

    /**
     * Set view data
     */
    public function with($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
        
        return $this;
    }

    /**
     * Check if view exists
     */
    public function exists($view)
    {
        return file_exists($this->getViewPath($view));
    }
}