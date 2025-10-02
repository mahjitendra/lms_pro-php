<?php

/**
 * Admin User Controller
 * LMS Pro - Learning Management System
 */

require_once __DIR__ . '/../../core/Controller.php';

class UserController extends Controller
{
    protected $middleware = ['auth', 'role:admin,super_admin'];

    /**
     * Display users list
     */
    public function index()
    {
        $search = $this->query('search');
        $role = $this->query('role');
        $status = $this->query('status');
        $page = (int)$this->query('page', 1);
        $perPage = (int)$this->query('per_page', 20);
        
        $query = $this->database->table('users u')
            ->leftJoin('user_roles ur', 'u.id = ur.user_id')
            ->leftJoin('roles r', 'ur.role_id = r.id')
            ->select([
                'u.*',
                'r.name as role_name',
                'r.slug as role_slug'
            ]);
            
        // Apply search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('u.first_name', 'LIKE', "%{$search}%")
                  ->orWhere('u.last_name', 'LIKE', "%{$search}%")
                  ->orWhere('u.email', 'LIKE', "%{$search}%");
            });
        }
        
        // Apply role filter
        if ($role) {
            $query->where('r.slug', $role);
        }
        
        // Apply status filter
        if ($status !== null) {
            $query->where('u.status', $status);
        }
        
        // Get paginated results
        $users = $this->paginate($query, $perPage, $page);
        
        // Get roles for filter dropdown
        $roles = $this->database->table('roles')
            ->orderBy('name', 'ASC')
            ->get();
            
        $data = [
            'title' => 'Users - Admin',
            'users' => $users['data'],
            'pagination' => $users['pagination'],
            'roles' => $roles,
            'filters' => [
                'search' => $search,
                'role' => $role,
                'status' => $status
            ]
        ];
        
        return $this->view('admin/users/index', $data, 'admin');
    }

    /**
     * Show create user form
     */
    public function create()
    {
        $roles = $this->database->table('roles')
            ->orderBy('name', 'ASC')
            ->get();
            
        $data = [
            'title' => 'Create User - Admin',
            'roles' => $roles,
            'csrf_token' => $this->session->getCsrfToken()
        ];
        
        return $this->view('admin/users/create', $data, 'admin');
    }

    /**
     * Store new user
     */
    public function store()
    {
        $this->requirePermission('create_users');
        
        // Validate input
        $data = $this->validate([
            'first_name' => $this->input('first_name'),
            'last_name' => $this->input('last_name'),
            'email' => $this->input('email'),
            'password' => $this->input('password'),
            'password_confirmation' => $this->input('password_confirmation'),
            'role_id' => $this->input('role_id'),
            'status' => $this->input('status', USER_STATUS_ACTIVE),
            'send_welcome_email' => $this->input('send_welcome_email', false)
        ], [
            'first_name' => 'required|string|min:2|max:50',
            'last_name' => 'required|string|min:2|max:50',
            'email' => 'required|email|max:191',
            'password' => 'required|min:8|max:128',
            'password_confirmation' => 'required|same:password',
            'role_id' => 'required|integer',
            'status' => 'required|integer'
        ]);
        
        // Check if email already exists
        if ($this->database->table('users')->where('email', $data['email'])->exists()) {
            if ($this->request->isAjax()) {
                return $this->error('Email address already exists', [
                    'email' => ['Email address already exists']
                ], 422);
            }
            
            $this->setFlash('error', 'Email address already exists');
            return $this->redirectBack();
        }
        
        try {
            $this->database->beginTransaction();
            
            // Create user
            $userId = $this->database->insert('users', [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => $this->auth->hashPassword($data['password']),
                'status' => $data['status'],
                'email_verified_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Assign role
            $this->database->insert('user_roles', [
                'user_id' => $userId,
                'role_id' => $data['role_id'],
                'assigned_by' => $this->userId(),
                'assigned_at' => date('Y-m-d H:i:s')
            ]);
            
            // Create user profile
            $this->database->insert('user_profiles', [
                'user_id' => $userId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->database->commit();
            
            // Send welcome email if requested
            if ($data['send_welcome_email']) {
                $this->sendWelcomeEmail($data['email'], $data['first_name']);
            }
            
            // Log activity
            UserActivity::log($this->userId(), 'user_created', "Created user: {$data['email']}", 'user', $userId);
            
            if ($this->request->isAjax()) {
                return $this->success('User created successfully', ['user_id' => $userId]);
            }
            
            $this->setFlash('success', 'User created successfully');
            return $this->redirect('/admin/users');
            
        } catch (Exception $e) {
            $this->database->rollback();
            
            if ($this->request->isAjax()) {
                return $this->error('Failed to create user', [], 500);
            }
            
            $this->setFlash('error', 'Failed to create user');
            return $this->redirectBack();
        }
    }

    /**
     * Show user details
     */
    public function show($id)
    {
        $user = $this->database->table('users u')
            ->leftJoin('user_roles ur', 'u.id = ur.user_id')
            ->leftJoin('roles r', 'ur.role_id = r.id')
            ->leftJoin('user_profiles up', 'u.id = up.user_id')
            ->where('u.id', $id)
            ->select([
                'u.*',
                'r.name as role_name',
                'r.slug as role_slug',
                'up.bio',
                'up.skills',
                'up.interests'
            ])
            ->first();
            
        if (!$user) {
            return $this->abort(404);
        }
        
        // Get user statistics
        $stats = $this->getUserStats($id);
        
        // Get recent activities
        $activities = UserActivity::getUserActivities($id, 20);
        
        // Get enrollments
        $enrollments = $this->database->table('enrollments e')
            ->join('courses c', 'e.course_id = c.id')
            ->where('e.user_id', $id)
            ->select(['e.*', 'c.title as course_title', 'c.slug as course_slug'])
            ->orderBy('e.enrolled_at', 'DESC')
            ->limit(10)
            ->get();
            
        $data = [
            'title' => $user['first_name'] . ' ' . $user['last_name'] . ' - Admin',
            'user' => $user,
            'stats' => $stats,
            'activities' => $activities,
            'enrollments' => $enrollments
        ];
        
        return $this->view('admin/users/view', $data, 'admin');
    }

    /**
     * Show edit user form
     */
    public function edit($id)
    {
        $this->requirePermission('edit_users');
        
        $user = $this->database->table('users u')
            ->leftJoin('user_roles ur', 'u.id = ur.user_id')
            ->leftJoin('roles r', 'ur.role_id = r.id')
            ->where('u.id', $id)
            ->select(['u.*', 'r.id as role_id', 'r.name as role_name'])
            ->first();
            
        if (!$user) {
            return $this->abort(404);
        }
        
        $roles = $this->database->table('roles')
            ->orderBy('name', 'ASC')
            ->get();
            
        $data = [
            'title' => 'Edit User - Admin',
            'user' => $user,
            'roles' => $roles,
            'csrf_token' => $this->session->getCsrfToken()
        ];
        
        return $this->view('admin/users/edit', $data, 'admin');
    }

    /**
     * Update user
     */
    public function update($id)
    {
        $this->requirePermission('edit_users');
        
        $user = $this->database->table('users')->where('id', $id)->first();
        if (!$user) {
            return $this->abort(404);
        }
        
        // Validate input
        $data = $this->validate([
            'first_name' => $this->input('first_name'),
            'last_name' => $this->input('last_name'),
            'email' => $this->input('email'),
            'password' => $this->input('password'),
            'password_confirmation' => $this->input('password_confirmation'),
            'role_id' => $this->input('role_id'),
            'status' => $this->input('status')
        ], [
            'first_name' => 'required|string|min:2|max:50',
            'last_name' => 'required|string|min:2|max:50',
            'email' => 'required|email|max:191',
            'password' => 'nullable|min:8|max:128',
            'password_confirmation' => 'nullable|same:password',
            'role_id' => 'required|integer',
            'status' => 'required|integer'
        ]);
        
        // Check if email exists for other users
        if ($this->database->table('users')
            ->where('email', $data['email'])
            ->where('id', '!=', $id)
            ->exists()) {
            
            if ($this->request->isAjax()) {
                return $this->error('Email address already exists', [
                    'email' => ['Email address already exists']
                ], 422);
            }
            
            $this->setFlash('error', 'Email address already exists');
            return $this->redirectBack();
        }
        
        try {
            $this->database->beginTransaction();
            
            // Update user
            $updateData = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'status' => $data['status'],
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Update password if provided
            if (!empty($data['password'])) {
                $updateData['password'] = $this->auth->hashPassword($data['password']);
            }
            
            $this->database->update('users', $updateData, 'id = :id', ['id' => $id]);
            
            // Update role
            $this->database->delete('user_roles', 'user_id = :user_id', ['user_id' => $id]);
            $this->database->insert('user_roles', [
                'user_id' => $id,
                'role_id' => $data['role_id'],
                'assigned_by' => $this->userId(),
                'assigned_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->database->commit();
            
            // Log activity
            UserActivity::log($this->userId(), 'user_updated', "Updated user: {$data['email']}", 'user', $id);
            
            if ($this->request->isAjax()) {
                return $this->success('User updated successfully');
            }
            
            $this->setFlash('success', 'User updated successfully');
            return $this->redirect('/admin/users/' . $id);
            
        } catch (Exception $e) {
            $this->database->rollback();
            
            if ($this->request->isAjax()) {
                return $this->error('Failed to update user', [], 500);
            }
            
            $this->setFlash('error', 'Failed to update user');
            return $this->redirectBack();
        }
    }

    /**
     * Delete user
     */
    public function delete($id)
    {
        $this->requirePermission('delete_users');
        
        $user = $this->database->table('users')->where('id', $id)->first();
        if (!$user) {
            return $this->abort(404);
        }
        
        // Prevent deleting own account
        if ($id == $this->userId()) {
            if ($this->request->isAjax()) {
                return $this->error('You cannot delete your own account', [], 403);
            }
            
            $this->setFlash('error', 'You cannot delete your own account');
            return $this->redirectBack();
        }
        
        try {
            $this->database->beginTransaction();
            
            // Delete related data
            $this->database->delete('user_roles', 'user_id = :user_id', ['user_id' => $id]);
            $this->database->delete('user_permissions', 'user_id = :user_id', ['user_id' => $id]);
            $this->database->delete('user_profiles', 'user_id = :user_id', ['user_id' => $id]);
            $this->database->delete('user_activities', 'user_id = :user_id', ['user_id' => $id]);
            
            // Delete enrollments (or you might want to keep them for data integrity)
            $this->database->delete('enrollments', 'user_id = :user_id', ['user_id' => $id]);
            
            // Delete user
            $this->database->delete('users', 'id = :id', ['id' => $id]);
            
            $this->database->commit();
            
            // Log activity
            UserActivity::log($this->userId(), 'user_deleted', "Deleted user: {$user['email']}", 'user', $id);
            
            if ($this->request->isAjax()) {
                return $this->success('User deleted successfully');
            }
            
            $this->setFlash('success', 'User deleted successfully');
            return $this->redirect('/admin/users');
            
        } catch (Exception $e) {
            $this->database->rollback();
            
            if ($this->request->isAjax()) {
                return $this->error('Failed to delete user', [], 500);
            }
            
            $this->setFlash('error', 'Failed to delete user');
            return $this->redirectBack();
        }
    }

    /**
     * Suspend user
     */
    public function suspend($id)
    {
        $this->requirePermission('suspend_users');
        
        $reason = $this->input('reason', 'Suspended by administrator');
        
        $updated = $this->database->update('users', [
            'status' => USER_STATUS_SUSPENDED,
            'suspension_reason' => $reason,
            'suspended_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $id]);
        
        if ($updated) {
            UserActivity::log($this->userId(), 'user_suspended', "Suspended user ID: {$id}", 'user', $id);
            
            if ($this->request->isAjax()) {
                return $this->success('User suspended successfully');
            }
            
            $this->setFlash('success', 'User suspended successfully');
        } else {
            if ($this->request->isAjax()) {
                return $this->error('Failed to suspend user', [], 500);
            }
            
            $this->setFlash('error', 'Failed to suspend user');
        }
        
        return $this->redirectBack();
    }

    /**
     * Activate user
     */
    public function activate($id)
    {
        $this->requirePermission('suspend_users');
        
        $updated = $this->database->update('users', [
            'status' => USER_STATUS_ACTIVE,
            'suspension_reason' => null,
            'suspended_at' => null,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $id]);
        
        if ($updated) {
            UserActivity::log($this->userId(), 'user_activated', "Activated user ID: {$id}", 'user', $id);
            
            if ($this->request->isAjax()) {
                return $this->success('User activated successfully');
            }
            
            $this->setFlash('success', 'User activated successfully');
        } else {
            if ($this->request->isAjax()) {
                return $this->error('Failed to activate user', [], 500);
            }
            
            $this->setFlash('error', 'Failed to activate user');
        }
        
        return $this->redirectBack();
    }

    /**
     * Get user statistics
     */
    private function getUserStats($userId)
    {
        $stats = [];
        
        // Total enrollments
        $stats['total_enrollments'] = $this->database->table('enrollments')
            ->where('user_id', $userId)
            ->count();
            
        // Completed courses
        $stats['completed_courses'] = $this->database->table('enrollments')
            ->where('user_id', $userId)
            ->where('status', ENROLLMENT_STATUS_COMPLETED)
            ->count();
            
        // Certificates earned
        $stats['certificates_earned'] = $this->database->table('certificates')
            ->where('user_id', $userId)
            ->where('status', CERTIFICATE_STATUS_ISSUED)
            ->count();
            
        // Total learning time
        $timeResult = $this->database->table('lesson_progress')
            ->where('user_id', $userId)
            ->selectRaw('SUM(time_spent) as total_time')
            ->first();
        $stats['total_learning_time'] = $timeResult ? (int)$timeResult['total_time'] : 0;
        
        // Average quiz score
        $quizResult = $this->database->table('quiz_attempts')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->selectRaw('AVG(score) as avg_score')
            ->first();
        $stats['avg_quiz_score'] = $quizResult ? round($quizResult['avg_score'], 1) : 0;
        
        return $stats;
    }

    /**
     * Send welcome email
     */
    private function sendWelcomeEmail($email, $firstName)
    {
        try {
            $subject = 'Welcome to ' . APP_NAME;
            $message = "
                <h2>Welcome to " . APP_NAME . "!</h2>
                <p>Hi {$firstName},</p>
                <p>Your account has been created by an administrator.</p>
                <p>You can now login and start learning!</p>
                <p>Best regards,<br>The " . APP_NAME . " Team</p>
            ";
            
            Helper::sendEmail($email, $subject, $message);
            
        } catch (Exception $e) {
            $this->log('Failed to send welcome email: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Export users data
     */
    public function export()
    {
        $format = $this->query('format', 'csv');
        
        $users = $this->database->table('users u')
            ->leftJoin('user_roles ur', 'u.id = ur.user_id')
            ->leftJoin('roles r', 'ur.role_id = r.id')
            ->select([
                'u.id',
                'u.first_name',
                'u.last_name',
                'u.email',
                'u.status',
                'r.name as role',
                'u.created_at',
                'u.last_login_at'
            ])
            ->orderBy('u.created_at', 'DESC')
            ->get();
            
        if ($format === 'json') {
            $this->response->setHeader('Content-Type', 'application/json');
            $this->response->setHeader('Content-Disposition', 'attachment; filename="users_' . date('Y-m-d') . '.json"');
            return json_encode($users, JSON_PRETTY_PRINT);
        }
        
        // CSV format
        $output = fopen('php://temp', 'r+');
        
        // Add header row
        fputcsv($output, ['ID', 'First Name', 'Last Name', 'Email', 'Status', 'Role', 'Created At', 'Last Login']);
        
        // Add data rows
        foreach ($users as $user) {
            fputcsv($output, [
                $user['id'],
                $user['first_name'],
                $user['last_name'],
                $user['email'],
                $user['status'],
                $user['role'],
                $user['created_at'],
                $user['last_login_at']
            ]);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        $this->response->setHeader('Content-Type', 'text/csv');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="users_' . date('Y-m-d') . '.csv"');
        return $csv;
    }
}