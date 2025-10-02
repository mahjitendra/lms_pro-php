<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Student Dashboard' ?></title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/css/student.css" rel="stylesheet">
    
    <!-- Meta Tags -->
    <meta name="csrf-token" content="<?= $this->session->getCsrfToken() ?>">
</head>
<body class="student-layout">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container">
            <a class="navbar-brand" href="/student/dashboard">
                <i class="fas fa-graduation-cap text-primary"></i>
                <strong><?= APP_NAME ?></strong>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/student/dashboard">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/student/courses">
                            <i class="fas fa-book"></i> My Courses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/courses">
                            <i class="fas fa-search"></i> Browse Courses
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-robot"></i> AI Tools
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/ai/computer-vision">Computer Vision</a></li>
                            <li><a class="dropdown-item" href="/ai/nlp">NLP Tools</a></li>
                            <li><a class="dropdown-item" href="/ai/machine-learning">ML Experiments</a></li>
                            <li><a class="dropdown-item" href="/ai/deep-learning">Deep Learning</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/forums">
                            <i class="fas fa-comments"></i> Forums
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notification-count">
                                0
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" style="width: 300px;">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <div id="notifications-list">
                                <li><span class="dropdown-item-text text-muted">No new notifications</span></li>
                            </div>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="/notifications">View All</a></li>
                        </ul>
                    </li>
                    
                    <!-- User Menu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                            <img src="<?= $this->user()['avatar'] ?? Helper::gravatar($this->user()['email']) ?>" 
                                 alt="Avatar" class="rounded-circle me-2" width="32" height="32">
                            <span><?= $this->user()['first_name'] ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/student/profile">
                                <i class="fas fa-user"></i> Profile
                            </a></li>
                            <li><a class="dropdown-item" href="/student/progress">
                                <i class="fas fa-chart-line"></i> Progress
                            </a></li>
                            <li><a class="dropdown-item" href="/student/certificates">
                                <i class="fas fa-certificate"></i> Certificates
                            </a></li>
                            <li><a class="dropdown-item" href="/settings">
                                <i class="fas fa-cog"></i> Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/logout">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-5 pt-4">
        <!-- Flash Messages -->
        <?php if ($this->flash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i>
                <?= $this->escape($this->flash('success')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($this->flash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <?= $this->escape($this->flash('error')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($this->flash('warning')): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <?= $this->escape($this->flash('warning')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($this->flash('info')): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle"></i>
                <?= $this->escape($this->flash('info')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Page Content -->
        <?= $content ?>
    </div>

    <!-- Footer -->
    <footer class="bg-light mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="text-muted mb-0">&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-end">
                    <a href="/help" class="text-muted me-3">Help</a>
                    <a href="/privacy" class="text-muted me-3">Privacy</a>
                    <a href="/terms" class="text-muted">Terms</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="/js/main.js"></script>
    <script src="/js/student.js"></script>
    
    <script>
        // Set CSRF token for AJAX requests
        window.csrfToken = '<?= $this->session->getCsrfToken() ?>';
        window.userId = <?= $this->user()['id'] ?>;
        
        // Load notifications
        document.addEventListener('DOMContentLoaded', function() {
            loadNotifications();
            
            // Refresh notifications every 30 seconds
            setInterval(loadNotifications, 30000);
        });
        
        function loadNotifications() {
            fetch('/student/notifications', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': window.csrfToken
                }
            })
            .then(response => response.json())
            .then(data => {
                const count = data.filter(n => !n.read_at).length;
                document.getElementById('notification-count').textContent = count;
                document.getElementById('notification-count').style.display = count > 0 ? 'block' : 'none';
                
                const list = document.getElementById('notifications-list');
                if (data.length > 0) {
                    list.innerHTML = data.slice(0, 5).map(notification => `
                        <li>
                            <a class="dropdown-item ${!notification.read_at ? 'fw-bold' : ''}" 
                               href="#" onclick="markAsRead(${notification.id})">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-${getNotificationIcon(notification.type)} text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-2">
                                        <div class="fw-semibold">${notification.title}</div>
                                        <div class="small text-muted">${notification.message}</div>
                                        <div class="small text-muted">${timeAgo(notification.created_at)}</div>
                                    </div>
                                </div>
                            </a>
                        </li>
                    `).join('');
                } else {
                    list.innerHTML = '<li><span class="dropdown-item-text text-muted">No new notifications</span></li>';
                }
            })
            .catch(error => console.error('Error loading notifications:', error));
        }
        
        function markAsRead(notificationId) {
            fetch(`/student/notifications/${notificationId}/mark-read`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': window.csrfToken
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotifications();
                }
            });
        }
        
        function getNotificationIcon(type) {
            const icons = {
                'course': 'book',
                'quiz': 'question-circle',
                'assignment': 'tasks',
                'certificate': 'certificate',
                'badge': 'medal',
                'announcement': 'bullhorn',
                'message': 'envelope',
                'default': 'bell'
            };
            return icons[type] || icons.default;
        }
        
        function timeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);
            
            if (diffInSeconds < 60) return 'just now';
            if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + 'm ago';
            if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + 'h ago';
            return Math.floor(diffInSeconds / 86400) + 'd ago';
        }
    </script>
</body>
</html>