<?php

/**
 * Payment Model
 * LMS Pro - Learning Management System
 */

require_once __DIR__ . '/../../core/Model.php';

class Payment extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'user_id', 'course_id', 'enrollment_id', 'subscription_id',
        'payment_method_id', 'gateway', 'gateway_transaction_id',
        'amount', 'currency', 'status', 'payment_date', 'metadata'
    ];
    
    protected $casts = [
        'user_id' => 'integer',
        'course_id' => 'integer',
        'enrollment_id' => 'integer',
        'subscription_id' => 'integer',
        'payment_method_id' => 'integer',
        'amount' => 'float',
        'payment_date' => 'datetime',
        'metadata' => 'json'
    ];

    /**
     * Get user
     */
    public function user()
    {
        return $this->database->table('users')
            ->where('id', $this->user_id)
            ->first();
    }

    /**
     * Get course
     */
    public function course()
    {
        if (!$this->course_id) {
            return null;
        }
        
        return $this->database->table('courses')
            ->where('id', $this->course_id)
            ->first();
    }

    /**
     * Get enrollment
     */
    public function enrollment()
    {
        if (!$this->enrollment_id) {
            return null;
        }
        
        return $this->database->table('enrollments')
            ->where('id', $this->enrollment_id)
            ->first();
    }

    /**
     * Get subscription
     */
    public function subscription()
    {
        if (!$this->subscription_id) {
            return null;
        }
        
        return $this->database->table('subscriptions')
            ->where('id', $this->subscription_id)
            ->first();
    }

    /**
     * Get payment method
     */
    public function paymentMethod()
    {
        if (!$this->payment_method_id) {
            return null;
        }
        
        return $this->database->table('payment_methods')
            ->where('id', $this->payment_method_id)
            ->first();
    }

    /**
     * Check if payment is completed
     */
    public function isCompleted()
    {
        return $this->status === PAYMENT_STATUS_COMPLETED;
    }

    /**
     * Check if payment is pending
     */
    public function isPending()
    {
        return $this->status === PAYMENT_STATUS_PENDING;
    }

    /**
     * Check if payment failed
     */
    public function isFailed()
    {
        return $this->status === PAYMENT_STATUS_FAILED;
    }

    /**
     * Check if payment is refunded
     */
    public function isRefunded()
    {
        return $this->status === PAYMENT_STATUS_REFUNDED;
    }

    /**
     * Mark payment as completed
     */
    public function markAsCompleted($transactionId = null)
    {
        $updateData = [
            'status' => PAYMENT_STATUS_COMPLETED,
            'payment_date' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($transactionId) {
            $updateData['gateway_transaction_id'] = $transactionId;
        }
        
        $updated = $this->database->update('payments', $updateData, 'id = :id', ['id' => $this->id]);
        
        if ($updated) {
            // Process successful payment
            $this->processSuccessfulPayment();
        }
        
        return $updated > 0;
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed($reason = null)
    {
        $metadata = $this->getMetadata();
        if ($reason) {
            $metadata['failure_reason'] = $reason;
        }
        
        return $this->database->update('payments', [
            'status' => PAYMENT_STATUS_FAILED,
            'metadata' => json_encode($metadata),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $this->id]);
    }

    /**
     * Process successful payment
     */
    private function processSuccessfulPayment()
    {
        // If it's a course payment, activate enrollment
        if ($this->course_id && $this->enrollment_id) {
            $this->database->update('enrollments', [
                'status' => ENROLLMENT_STATUS_ACTIVE,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $this->enrollment_id]);
            
            // Log activity
            UserActivity::log($this->user_id, 'payment_completed', 
                "Payment completed for course enrollment", 'payment', $this->id);
        }
        
        // If it's a subscription payment, activate subscription
        if ($this->subscription_id) {
            $this->database->update('subscriptions', [
                'status' => SUBSCRIPTION_STATUS_ACTIVE,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $this->subscription_id]);
        }
        
        // Send payment confirmation email
        $this->sendPaymentConfirmation();
    }

    /**
     * Send payment confirmation email
     */
    private function sendPaymentConfirmation()
    {
        try {
            $user = $this->user();
            $course = $this->course();
            
            if (!$user) {
                return;
            }
            
            $subject = 'Payment Confirmation - ' . APP_NAME;
            $courseName = $course ? $course['title'] : 'Subscription';
            
            $message = "
                <h2>Payment Confirmation</h2>
                <p>Hi {$user['first_name']},</p>
                <p>Your payment has been successfully processed!</p>
                <h3>Payment Details:</h3>
                <ul>
                    <li><strong>Item:</strong> {$courseName}</li>
                    <li><strong>Amount:</strong> " . Helper::formatCurrency($this->amount, $this->currency) . "</li>
                    <li><strong>Payment Date:</strong> " . date('M d, Y H:i', strtotime($this->payment_date)) . "</li>
                    <li><strong>Transaction ID:</strong> {$this->gateway_transaction_id}</li>
                </ul>
                <p>You can now access your purchased content.</p>
                <p>Thank you for your purchase!</p>
                <p>The " . APP_NAME . " Team</p>
            ";
            
            Helper::sendEmail($user['email'], $subject, $message);
            
        } catch (Exception $e) {
            Helper::log('Failed to send payment confirmation email: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Get payment metadata
     */
    public function getMetadata($key = null)
    {
        if (!$this->metadata) {
            return $key ? null : [];
        }
        
        $metadata = json_decode($this->metadata, true);
        
        if ($key) {
            return Helper::arrayGet($metadata, $key);
        }
        
        return $metadata;
    }

    /**
     * Update payment metadata
     */
    public function updateMetadata($key, $value = null)
    {
        $metadata = $this->getMetadata();
        
        if (is_array($key)) {
            $metadata = array_merge($metadata, $key);
        } else {
            Helper::arraySet($metadata, $key, $value);
        }
        
        return $this->database->update('payments', [
            'metadata' => json_encode($metadata),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $this->id]);
    }

    /**
     * Create refund
     */
    public function refund($amount = null, $reason = null)
    {
        if (!$this->isCompleted()) {
            throw new Exception('Can only refund completed payments');
        }
        
        $refundAmount = $amount ?: $this->amount;
        
        if ($refundAmount > $this->amount) {
            throw new Exception('Refund amount cannot exceed original payment amount');
        }
        
        // Create refund record
        $refundData = [
            'payment_id' => $this->id,
            'amount' => $refundAmount,
            'currency' => $this->currency,
            'reason' => $reason,
            'status' => 'pending',
            'requested_by' => App::getInstance()->get('auth')->id(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $refundId = $this->database->insert('refunds', $refundData);
        
        // Update payment status if full refund
        if ($refundAmount >= $this->amount) {
            $this->database->update('payments', [
                'status' => PAYMENT_STATUS_REFUNDED,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $this->id]);
        }
        
        return $refundId;
    }

    /**
     * Get payment refunds
     */
    public function getRefunds()
    {
        return $this->database->table('refunds')
            ->where('payment_id', $this->id)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    /**
     * Get total refunded amount
     */
    public function getTotalRefunded()
    {
        $result = $this->database->table('refunds')
            ->where('payment_id', $this->id)
            ->where('status', 'completed')
            ->selectRaw('SUM(amount) as total')
            ->first();
            
        return $result ? $result['total'] : 0;
    }

    /**
     * Get payments by user
     */
    public static function getByUser($userId, $status = null)
    {
        $database = App::getInstance()->getDatabase();
        $query = $database->table('payments p')
            ->leftJoin('courses c', 'p.course_id = c.id')
            ->leftJoin('subscriptions s', 'p.subscription_id = s.id')
            ->where('p.user_id', $userId)
            ->select([
                'p.*',
                'c.title as course_title',
                's.plan_name as subscription_plan'
            ]);
            
        if ($status) {
            $query->where('p.status', $status);
        }
        
        return $query->orderBy('p.created_at', 'DESC')->get();
    }

    /**
     * Get revenue statistics
     */
    public static function getRevenueStats($dateFrom = null, $dateTo = null)
    {
        $database = App::getInstance()->getDatabase();
        $query = $database->table('payments')
            ->where('status', PAYMENT_STATUS_COMPLETED);
            
        if ($dateFrom) {
            $query->where('payment_date', '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $query->where('payment_date', '<=', $dateTo);
        }
        
        $stats = $query->selectRaw('
            COUNT(*) as total_payments,
            SUM(amount) as total_revenue,
            AVG(amount) as avg_payment,
            MIN(amount) as min_payment,
            MAX(amount) as max_payment
        ')->first();
        
        return [
            'total_payments' => (int)$stats['total_payments'],
            'total_revenue' => (float)$stats['total_revenue'],
            'avg_payment' => round($stats['avg_payment'], 2),
            'min_payment' => (float)$stats['min_payment'],
            'max_payment' => (float)$stats['max_payment']
        ];
    }

    /**
     * Get monthly revenue data
     */
    public static function getMonthlyRevenue($months = 12)
    {
        $database = App::getInstance()->getDatabase();
        return $database->table('payments')
            ->where('status', PAYMENT_STATUS_COMPLETED)
            ->where('payment_date', '>=', date('Y-m-d', strtotime("-{$months} months")))
            ->selectRaw('
                DATE_FORMAT(payment_date, "%Y-%m") as month,
                SUM(amount) as revenue,
                COUNT(*) as payment_count
            ')
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->get();
    }
}