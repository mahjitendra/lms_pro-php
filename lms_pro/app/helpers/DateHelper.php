<?php

/**
 * Date Helper Functions
 * LMS Pro - Learning Management System
 */

class DateHelper
{
    /**
     * Format date for display
     */
    public static function format($date, $format = 'M d, Y')
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
     * Format datetime for display
     */
    public static function formatDateTime($datetime, $format = 'M d, Y H:i')
    {
        return self::format($datetime, $format);
    }

    /**
     * Get time ago format
     */
    public static function timeAgo($datetime)
    {
        if (!$datetime) {
            return '';
        }
        
        $time = time() - strtotime($datetime);
        
        if ($time < 60) {
            return 'just now';
        }
        
        $condition = [
            12 * 30 * 24 * 60 * 60 => 'year',
            30 * 24 * 60 * 60 => 'month',
            7 * 24 * 60 * 60 => 'week',
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
     * Get human readable duration
     */
    public static function duration($seconds)
    {
        if ($seconds < 60) {
            return $seconds . 's';
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($minutes < 60) {
            return $remainingSeconds > 0 ? 
                $minutes . 'm ' . $remainingSeconds . 's' : 
                $minutes . 'm';
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($hours < 24) {
            return $remainingMinutes > 0 ? 
                $hours . 'h ' . $remainingMinutes . 'm' : 
                $hours . 'h';
        }
        
        $days = floor($hours / 24);
        $remainingHours = $hours % 24;
        
        return $remainingHours > 0 ? 
            $days . 'd ' . $remainingHours . 'h' : 
            $days . 'd';
    }

    /**
     * Check if date is today
     */
    public static function isToday($date)
    {
        return date('Y-m-d', strtotime($date)) === date('Y-m-d');
    }

    /**
     * Check if date is yesterday
     */
    public static function isYesterday($date)
    {
        return date('Y-m-d', strtotime($date)) === date('Y-m-d', strtotime('-1 day'));
    }

    /**
     * Check if date is this week
     */
    public static function isThisWeek($date)
    {
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekEnd = date('Y-m-d', strtotime('sunday this week'));
        $dateFormatted = date('Y-m-d', strtotime($date));
        
        return $dateFormatted >= $weekStart && $dateFormatted <= $weekEnd;
    }

    /**
     * Check if date is this month
     */
    public static function isThisMonth($date)
    {
        return date('Y-m', strtotime($date)) === date('Y-m');
    }

    /**
     * Get start of day
     */
    public static function startOfDay($date = null)
    {
        $date = $date ?: date('Y-m-d');
        return date('Y-m-d 00:00:00', strtotime($date));
    }

    /**
     * Get end of day
     */
    public static function endOfDay($date = null)
    {
        $date = $date ?: date('Y-m-d');
        return date('Y-m-d 23:59:59', strtotime($date));
    }

    /**
     * Get start of week
     */
    public static function startOfWeek($date = null)
    {
        $date = $date ?: date('Y-m-d');
        return date('Y-m-d 00:00:00', strtotime('monday this week', strtotime($date)));
    }

    /**
     * Get end of week
     */
    public static function endOfWeek($date = null)
    {
        $date = $date ?: date('Y-m-d');
        return date('Y-m-d 23:59:59', strtotime('sunday this week', strtotime($date)));
    }

    /**
     * Get start of month
     */
    public static function startOfMonth($date = null)
    {
        $date = $date ?: date('Y-m-d');
        return date('Y-m-01 00:00:00', strtotime($date));
    }

    /**
     * Get end of month
     */
    public static function endOfMonth($date = null)
    {
        $date = $date ?: date('Y-m-d');
        return date('Y-m-t 23:59:59', strtotime($date));
    }

    /**
     * Add days to date
     */
    public static function addDays($date, $days)
    {
        return date('Y-m-d H:i:s', strtotime($date . " +{$days} days"));
    }

    /**
     * Subtract days from date
     */
    public static function subDays($date, $days)
    {
        return date('Y-m-d H:i:s', strtotime($date . " -{$days} days"));
    }

    /**
     * Get difference in days
     */
    public static function diffInDays($date1, $date2)
    {
        $datetime1 = new DateTime($date1);
        $datetime2 = new DateTime($date2);
        $interval = $datetime1->diff($datetime2);
        
        return $interval->days;
    }

    /**
     * Get difference in hours
     */
    public static function diffInHours($date1, $date2)
    {
        $datetime1 = new DateTime($date1);
        $datetime2 = new DateTime($date2);
        $interval = $datetime1->diff($datetime2);
        
        return ($interval->days * 24) + $interval->h;
    }

    /**
     * Get difference in minutes
     */
    public static function diffInMinutes($date1, $date2)
    {
        $datetime1 = new DateTime($date1);
        $datetime2 = new DateTime($date2);
        $interval = $datetime1->diff($datetime2);
        
        return ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
    }

    /**
     * Check if date is in the past
     */
    public static function isPast($date)
    {
        return strtotime($date) < time();
    }

    /**
     * Check if date is in the future
     */
    public static function isFuture($date)
    {
        return strtotime($date) > time();
    }

    /**
     * Get timezone list
     */
    public static function getTimezones()
    {
        $timezones = [];
        $identifiers = DateTimeZone::listIdentifiers();
        
        foreach ($identifiers as $identifier) {
            $timezone = new DateTimeZone($identifier);
            $datetime = new DateTime('now', $timezone);
            $offset = $datetime->format('P');
            
            $timezones[$identifier] = "({$offset}) " . str_replace('_', ' ', $identifier);
        }
        
        return $timezones;
    }

    /**
     * Convert timezone
     */
    public static function convertTimezone($date, $fromTimezone, $toTimezone)
    {
        $datetime = new DateTime($date, new DateTimeZone($fromTimezone));
        $datetime->setTimezone(new DateTimeZone($toTimezone));
        
        return $datetime->format('Y-m-d H:i:s');
    }

    /**
     * Get user's local time
     */
    public static function toUserTimezone($date, $userTimezone = null)
    {
        if (!$userTimezone) {
            // Get from user preferences or default to UTC
            $auth = App::getInstance()->get('auth');
            if ($auth->check()) {
                $user = $auth->user();
                $userTimezone = $user['timezone'] ?? 'UTC';
            } else {
                $userTimezone = 'UTC';
            }
        }
        
        return self::convertTimezone($date, 'UTC', $userTimezone);
    }

    /**
     * Get age from birth date
     */
    public static function getAge($birthDate)
    {
        if (!$birthDate) {
            return null;
        }
        
        $birth = new DateTime($birthDate);
        $today = new DateTime();
        $age = $birth->diff($today);
        
        return $age->y;
    }

    /**
     * Get date range
     */
    public static function getDateRange($startDate, $endDate, $format = 'Y-m-d')
    {
        $dates = [];
        $current = strtotime($startDate);
        $end = strtotime($endDate);
        
        while ($current <= $end) {
            $dates[] = date($format, $current);
            $current = strtotime('+1 day', $current);
        }
        
        return $dates;
    }

    /**
     * Get business days between dates
     */
    public static function getBusinessDays($startDate, $endDate)
    {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($start, $interval, $end);
        
        $businessDays = 0;
        
        foreach ($dateRange as $date) {
            $dayOfWeek = $date->format('N');
            if ($dayOfWeek < 6) { // Monday = 1, Sunday = 7
                $businessDays++;
            }
        }
        
        return $businessDays;
    }
}