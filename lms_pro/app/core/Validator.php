<?php

/**
 * Input Validation Class
 * LMS Pro - Learning Management System
 */

class Validator
{
    private $data = [];
    private $rules = [];
    private $messages = [];
    private $errors = [];
    private $customMessages = [];

    public function __construct()
    {
        $this->setDefaultMessages();
    }

    /**
     * Set default error messages
     */
    private function setDefaultMessages()
    {
        $this->messages = [
            'required' => 'The :field field is required.',
            'email' => 'The :field must be a valid email address.',
            'min' => 'The :field must be at least :min characters.',
            'max' => 'The :field must not exceed :max characters.',
            'numeric' => 'The :field must be a number.',
            'integer' => 'The :field must be an integer.',
            'alpha' => 'The :field may only contain letters.',
            'alpha_num' => 'The :field may only contain letters and numbers.',
            'alpha_dash' => 'The :field may only contain letters, numbers, dashes and underscores.',
            'url' => 'The :field must be a valid URL.',
            'date' => 'The :field must be a valid date.',
            'date_format' => 'The :field must match the format :format.',
            'before' => 'The :field must be before :date.',
            'after' => 'The :field must be after :date.',
            'confirmed' => 'The :field confirmation does not match.',
            'same' => 'The :field and :other must match.',
            'different' => 'The :field and :other must be different.',
            'in' => 'The selected :field is invalid.',
            'not_in' => 'The selected :field is invalid.',
            'unique' => 'The :field has already been taken.',
            'exists' => 'The selected :field is invalid.',
            'regex' => 'The :field format is invalid.',
            'file' => 'The :field must be a file.',
            'image' => 'The :field must be an image.',
            'mimes' => 'The :field must be a file of type: :values.',
            'max_file_size' => 'The :field may not be greater than :max kilobytes.',
            'dimensions' => 'The :field has invalid image dimensions.',
            'json' => 'The :field must be a valid JSON string.',
            'boolean' => 'The :field field must be true or false.',
            'array' => 'The :field must be an array.',
            'string' => 'The :field must be a string.',
            'phone' => 'The :field must be a valid phone number.',
            'ip' => 'The :field must be a valid IP address.',
            'ipv4' => 'The :field must be a valid IPv4 address.',
            'ipv6' => 'The :field must be a valid IPv6 address.',
            'mac_address' => 'The :field must be a valid MAC address.',
            'uuid' => 'The :field must be a valid UUID.',
            'timezone' => 'The :field must be a valid timezone.',
        ];
    }

    /**
     * Validate data against rules
     */
    public function validate($data, $rules, $customMessages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $customMessages;
        $this->errors = [];

        foreach ($this->rules as $field => $fieldRules) {
            $this->validateField($field, $fieldRules);
        }

        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors,
            'data' => $this->getValidatedData()
        ];
    }

    /**
     * Validate a single field
     */
    private function validateField($field, $rules)
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        $value = $this->getValue($field);

        foreach ($rules as $rule) {
            $this->validateRule($field, $value, $rule);
        }
    }

    /**
     * Validate a single rule
     */
    private function validateRule($field, $value, $rule)
    {
        if (strpos($rule, ':') !== false) {
            list($ruleName, $parameters) = explode(':', $rule, 2);
            $parameters = explode(',', $parameters);
        } else {
            $ruleName = $rule;
            $parameters = [];
        }

        $method = 'validate' . ucfirst(camelCase($ruleName));

        if (method_exists($this, $method)) {
            $passes = $this->$method($field, $value, $parameters);
            
            if (!$passes) {
                $this->addError($field, $ruleName, $parameters);
            }
        }
    }

    /**
     * Get field value
     */
    private function getValue($field)
    {
        if (strpos($field, '.') !== false) {
            return $this->getNestedValue($field);
        }

        return $this->data[$field] ?? null;
    }

    /**
     * Get nested field value
     */
    private function getNestedValue($field)
    {
        $keys = explode('.', $field);
        $value = $this->data;

        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Add validation error
     */
    private function addError($field, $rule, $parameters = [])
    {
        $message = $this->getMessage($field, $rule, $parameters);
        
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
    }

    /**
     * Get error message
     */
    private function getMessage($field, $rule, $parameters = [])
    {
        $key = "{$field}.{$rule}";
        
        if (isset($this->customMessages[$key])) {
            $message = $this->customMessages[$key];
        } elseif (isset($this->customMessages[$rule])) {
            $message = $this->customMessages[$rule];
        } else {
            $message = $this->messages[$rule] ?? 'The :field field is invalid.';
        }

        // Replace placeholders
        $message = str_replace(':field', $this->getFieldName($field), $message);
        
        foreach ($parameters as $i => $parameter) {
            $message = str_replace(':' . $i, $parameter, $message);
            
            // Common parameter names
            if ($i === 0) {
                $message = str_replace([':min', ':max', ':size', ':date', ':other', ':format'], $parameter, $message);
            }
        }
        
        if (!empty($parameters)) {
            $message = str_replace(':values', implode(', ', $parameters), $message);
        }

        return $message;
    }

    /**
     * Get human-readable field name
     */
    private function getFieldName($field)
    {
        return ucwords(str_replace(['_', '.'], ' ', $field));
    }

    /**
     * Get validated data
     */
    private function getValidatedData()
    {
        $validated = [];
        
        foreach ($this->rules as $field => $rules) {
            $value = $this->getValue($field);
            if ($value !== null) {
                $validated[$field] = $value;
            }
        }
        
        return $validated;
    }

    // Validation Rules

    /**
     * Required validation
     */
    protected function validateRequired($field, $value, $parameters)
    {
        if (is_null($value)) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        if (is_array($value) && empty($value)) {
            return false;
        }

        return true;
    }

    /**
     * Email validation
     */
    protected function validateEmail($field, $value, $parameters)
    {
        if (is_null($value)) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Minimum length validation
     */
    protected function validateMin($field, $value, $parameters)
    {
        if (is_null($value)) {
            return true;
        }

        $min = (int) $parameters[0];

        if (is_numeric($value)) {
            return $value >= $min;
        }

        return mb_strlen($value) >= $min;
    }

    /**
     * Maximum length validation
     */
    protected function validateMax($field, $value, $parameters)
    {
        if (is_null($value)) {
            return true;
        }

        $max = (int) $parameters[0];

        if (is_numeric($value)) {
            return $value <= $max;
        }

        return mb_strlen($value) <= $max;
    }

    /**
     * Numeric validation
     */
    protected function validateNumeric($field, $value, $parameters)
    {
        if (is_null($value)) {
            return true;
        }

        return is_numeric($value);
    }

    /**
     * Integer validation
     */
    protected function validateInteger($field, $value, $parameters)
    {
        if (is_null($value)) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Alpha validation (letters only)
     */
    protected function validateAlpha($field, $value, $parameters)
    {
        if (is_null($value)) {
            return true;
        }

        return preg_match('/^[a-zA-Z]+$/', $value);
    }

    /**
     * Alpha numeric validation
     */
    protected function validateAlphaNum($field, $value, $parameters)
    {
        if (is_null($value)) {
            return true;
        }

        return preg_match('/^[a-zA-Z0-9]+$/', $value);
    }

    /**
     * Alpha dash validation (letters, numbers, dashes, underscores)
     */
    protected function validateAlphaDash($field, $value, $parameters)
    {
        if (is_null($value)) {
            return true;
        }

        return preg_match('/^[a-zA-Z0-9_-]+$/', $value);
    }

    /**
     * URL validation
     */
    protected function validateUrl($field, $value, $parameters)
    {
        if (is_null($value)) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Date validation
     */
    protected function validateDate($field, $value, $parameters)
    {
        if (is_null($value)) {
            return true;
        }

        return strtotime($value) !== false;
    }

    /**
     * Date format validation
     */
    protected function validateDateFormat($field, $value, $parameters)
    {
        if (is_null($value)) {
            return true;
        }

        $format = $parameters[0];
        $date = DateTime::createFromFormat($format, $value);
        
        return $date && $date->format($format) === $value;
    }

    /**
     * Confirmed validation (field_confirmation must match)
     */
    protected function validateConfirmed($field, $value, $parameters)
    {
        $confirmationField = $field . '_confirmation';
        $confirmationValue = $this->getValue($confirmationField);
        
        return $value === $confirmationValue;
    }

    /**
     * Same validation (must match another field)
     */
    protected function validateSame($field, $value, $parameters)
    {
        $otherField = $parameters[0];
        $otherValue = $this->getValue($otherField);
        
        return $value === $otherValue;
    }

    /**
     * Different validation (must be different from another field)
     */
    protected function validateDifferent($field, $value, $parameters)
    {
        $otherField = $parameters[0];
        $otherValue = $this->getValue($otherField);
        
        return $value !== $otherValue;
    }

    /**
     * In validation (value must be in list)
     */
    protected function validateIn($field, $value, $parameters)
    {
        if (is_null($value)) {
            return true;
        }

        return in_array($value, $parameters);
    }

    /**
     * Not in validation (value must not be in list)
     */
    protected function validateNotIn($field, $value, $parameters)
    {
        if (is_null($value)) {
            return true;
        }

        return !in_array($value, $parameters);
    }

    /**
     * Regex validation
     */
    protected function validateRegex($field, $value, $parameters)
    {
        if (is_null($value)) {
            return true;
        }

        return preg_match($parameters[0], $value);
    }

    /**
     * Boolean validation
     */
    protected function validateBoolean($field, $value, $parameters)
    {
        if (is_null($value)) {
            return true;
        }

        return in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true);
    }

    /**
     * Array validation
     */
    protected function validateArray($field, $value, $parameters)
    {
        if (is_null($value)) {
            return true;
        }

        return is_array($value);
    }

    /**
     * String validation
     */
    protected function validateString($field, $value, $parameters)
    {
        if (is_null($value)) {
            return true;
        }

        return is_string($value);
    }

    /**
     * JSON validation
     */
    protected function validateJson($field, $value, $parameters)
    {
        if (is_null($value)) {
            return true;
        }

        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Phone validation
     */
    protected function validatePhone($field, $value, $parameters)
    {
        if (is_null($value)) {
            return true;
        }

        return preg_match('/^\+?[1-9]\d{1,14}$/', $value);
    }

    /**
     * IP address validation
     */
    protected function validateIp($field, $value, $parameters)
    {
        if (is_null($value)) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * IPv4 validation
     */
    protected function validateIpv4($field, $value, $parameters)
    {
        if (is_null($value)) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * IPv6 validation
     */
    protected function validateIpv6($field, $value, $parameters)
    {
        if (is_null($value)) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * UUID validation
     */
    protected function validateUuid($field, $value, $parameters)
    {
        if (is_null($value)) {
            return true;
        }

        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value);
    }

    /**
     * Get all errors
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Check if validation failed
     */
    public function fails()
    {
        return !empty($this->errors);
    }

    /**
     * Check if validation passed
     */
    public function passes()
    {
        return empty($this->errors);
    }
}

// Helper function for camel case conversion
if (!function_exists('camelCase')) {
    function camelCase($string) {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string))));
    }
}