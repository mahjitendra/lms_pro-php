<?php $this->extend('guest'); ?>

<div class="min-vh-100 d-flex align-items-center bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <!-- Logo -->
                        <div class="text-center mb-4">
                            <i class="fas fa-graduation-cap fa-3x text-primary mb-3"></i>
                            <h2 class="fw-bold"><?= APP_NAME ?></h2>
                            <p class="text-muted">Sign in to your account</p>
                        </div>

                        <!-- Login Form -->
                        <form id="loginForm" method="POST" action="/login/authenticate">
                            <?= $csrf_token ?>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" 
                                           class="form-control <?= $this->error('email') ? 'is-invalid' : '' ?>" 
                                           id="email" 
                                           name="email" 
                                           value="<?= $this->old('email') ?>" 
                                           placeholder="Enter your email"
                                           required>
                                    <?php if ($this->error('email')): ?>
                                        <div class="invalid-feedback">
                                            <?= $this->escape($this->error('email')) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control <?= $this->error('password') ? 'is-invalid' : '' ?>" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Enter your password"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($this->error('password')): ?>
                                        <div class="invalid-feedback">
                                            <?= $this->escape($this->error('password')) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
                                    <label class="form-check-label" for="remember">
                                        Remember me
                                    </label>
                                </div>
                                <a href="/forgot-password" class="text-decoration-none">
                                    Forgot password?
                                </a>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg" id="loginBtn">
                                    <i class="fas fa-sign-in-alt"></i>
                                    Sign In
                                </button>
                            </div>
                        </form>

                        <!-- Divider -->
                        <div class="text-center my-4">
                            <span class="text-muted">or</span>
                        </div>

                        <!-- Social Login -->
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-danger" onclick="socialLogin('google')">
                                <i class="fab fa-google"></i>
                                Continue with Google
                            </button>
                            <button class="btn btn-outline-primary" onclick="socialLogin('facebook')">
                                <i class="fab fa-facebook"></i>
                                Continue with Facebook
                            </button>
                        </div>

                        <!-- Register Link -->
                        <div class="text-center mt-4">
                            <p class="text-muted">
                                Don't have an account? 
                                <a href="/register" class="text-decoration-none fw-semibold">
                                    Sign up here
                                </a>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Demo Accounts (for development) -->
                <?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="card-title">Demo Accounts</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <button class="btn btn-sm btn-outline-secondary w-100" onclick="fillDemo('admin')">
                                    Admin Demo
                                </button>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-sm btn-outline-secondary w-100" onclick="fillDemo('instructor')">
                                    Instructor Demo
                                </button>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-sm btn-outline-secondary w-100" onclick="fillDemo('student')">
                                    Student Demo
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    // Toggle password visibility
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        const icon = this.querySelector('i');
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });

    // Handle form submission
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        loginBtn.disabled = true;
        loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
        
        const formData = new FormData(this);
        
        fetch('/login/authenticate', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showAlert('success', data.message);
                
                // Redirect after short delay
                setTimeout(() => {
                    window.location.href = data.data.redirect_url || '/dashboard';
                }, 1000);
            } else {
                showAlert('danger', data.message);
                loginBtn.disabled = false;
                loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
            }
        })
        .catch(error => {
            console.error('Login error:', error);
            showAlert('danger', 'An error occurred. Please try again.');
            loginBtn.disabled = false;
            loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
        });
    });
});

function socialLogin(provider) {
    window.location.href = `/auth/social/${provider}`;
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const form = document.getElementById('loginForm');
    form.parentNode.insertBefore(alertDiv, form);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

<?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
function fillDemo(type) {
    const credentials = {
        'admin': { email: 'admin@lmspro.com', password: 'admin123' },
        'instructor': { email: 'instructor@lmspro.com', password: 'instructor123' },
        'student': { email: 'student@lmspro.com', password: 'student123' }
    };
    
    if (credentials[type]) {
        document.getElementById('email').value = credentials[type].email;
        document.getElementById('password').value = credentials[type].password;
    }
}
<?php endif; ?>
</script>