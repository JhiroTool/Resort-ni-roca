<?php
session_start();

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if user is already logged in
if (isset($_SESSION['customer_id']) || isset($_SESSION['admin_id'])) {
    if (isset($_SESSION['customer_id'])) {
        header("Location: client/dashboard.php");
    } else {
        header("Location: admin/dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Paradise Resort</title>
    <link rel="stylesheet" href="assets/css/auth.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Role selection styles */
        .role-selection {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-radius: 15px;
            background: #f8f9fa;
            padding: 0.5rem;
        }
        
        .role-btn {
            flex: 1;
            padding: 1rem;
            background: transparent;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 600;
            color: #666;
        }
        
        .role-btn.active {
            background: white;
            color: #4a9960;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .role-btn.admin.active {
            color: #1c7ed6;
        }
        
        /* Dynamic theme switching */
        .auth-background.admin-theme {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        }
        
        .auth-card.admin-theme .auth-btn-primary {
            background: linear-gradient(135deg, #1c7ed6 0%, #4dabf7 100%);
        }
        
        .auth-card.admin-theme .form-group input:focus {
            border-color: #1c7ed6;
            box-shadow: 0 0 0 3px rgba(28, 126, 214, 0.1);
        }
        
        .auth-card.admin-theme .role-btn.admin.active,
        .auth-card.admin-theme .auth-link:hover,
        .auth-card.admin-theme .forgot-link:hover {
            color: #1c7ed6;
        }
        
        .logo.admin-theme i {
            color: #4dabf7;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-background" id="authBackground">
            <div class="auth-overlay"></div>
        </div>
        
        <div class="auth-content">
            <div class="auth-header">
                <a href="index.php" class="back-home">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Home</span>
                </a>
                <div class="logo" id="logoContainer">
                    <i class="fas fa-palm-tree" id="logoIcon"></i>
                    <h1 id="logoText">Paradise Resort</h1>
                </div>
            </div>

            <div class="auth-form-container">
                <div class="auth-card" id="authCard">
                    <div class="auth-card-header">
                        <h2 id="pageTitle">Welcome Back!</h2>
                        <p id="pageSubtitle">Choose your login type to continue</p>
                    </div>

                    <!-- Role Selection -->
                    <div class="role-selection">
                        <button type="button" class="role-btn client active" id="clientBtn">
                            <i class="fas fa-user"></i>
                            Customer
                        </button>
                        <button type="button" class="role-btn admin" id="adminBtn">
                            <i class="fas fa-shield-alt"></i>
                            Administrator
                        </button>
                    </div>

                    <form id="loginForm" class="auth-form" action="auth/login_handler.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="user_type" id="userType" value="client">
                        
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i>
                                <span id="emailLabel">Email Address</span>
                            </label>
                            <input type="email" id="email" name="email" required placeholder="Enter your email">
                            <span class="error-message" id="email-error"></span>
                        </div>

                        <div class="form-group">
                            <label for="password">
                                <i class="fas fa-lock"></i>
                                Password
                            </label>
                            <div class="password-input">
                                <input type="password" id="password" name="password" required placeholder="Enter your password">
                                <button type="button" class="password-toggle" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <span class="error-message" id="password-error"></span>
                        </div>

                        <div class="form-options">
                            <label class="checkbox-container">
                                <input type="checkbox" name="remember_me">
                                <span class="checkmark"></span>
                                <span id="rememberText">Remember me</span>
                            </label>
                            <a href="auth/forgot_password.php" class="forgot-link">Forgot Password?</a>
                        </div>

                        <button type="submit" class="auth-btn auth-btn-primary">
                            <span class="btn-text" id="submitText">
                                <i class="fas fa-sign-in-alt"></i>
                                Sign In
                            </span>
                            <div class="btn-loader" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </button>

                        <!-- Client-only social login -->
                        <div id="socialLoginSection">
                            <div class="auth-divider">
                                <span>or</span>
                            </div>

                            <div class="social-login">
                                <button type="button" class="social-btn google-btn">
                                    <i class="fab fa-google"></i>
                                    Continue with Google
                                </button>
                                <button type="button" class="social-btn facebook-btn">
                                    <i class="fab fa-facebook-f"></i>
                                    Continue with Facebook
                                </button>
                            </div>
                        </div>

                        <!-- Admin Security Notice -->
                        <div id="securityNotice" style="display: none; margin-top: 1.5rem; padding: 1rem; background: rgba(28, 126, 214, 0.1); border-radius: 8px; border-left: 4px solid #1c7ed6;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                <i class="fas fa-info-circle" style="color: #1c7ed6;"></i>
                                <strong style="color: #1c7ed6;">Security Notice</strong>
                            </div>
                            <p style="font-size: 0.85rem; color: #666; margin: 0;">
                                All admin activities are logged and monitored. Only authorized personnel should access this system.
                            </p>
                        </div>
                    </form>

                    <div class="auth-footer">
                        <p id="footerText">Don't have an account? <a href="register.php" class="auth-link">Create one here</a></p>
                        <p id="supportText" style="display: none;">Need help? <a href="mailto:support@paradiseresort.com" class="auth-link">Contact IT Support</a></p>
                    </div>
                </div>

                <div class="auth-benefits">
                    <h3 id="benefitsTitle">Why Book with Us?</h3>
                    <div id="clientBenefits">
                        <div class="benefit-item">
                            <i class="fas fa-check-circle"></i>
                            <div>
                                <h4>Best Price Guarantee</h4>
                                <p>We offer the lowest prices on all our accommodations</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h4>24/7 Customer Support</h4>
                                <p>Our team is always ready to assist you</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-shield-alt"></i>
                            <div>
                                <h4>Secure Booking</h4>
                                <p>Your personal information is safe with us</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-calendar-check"></i>
                            <div>
                                <h4>Easy Management</h4>
                                <p>View and manage your bookings easily</p>
                            </div>
                        </div>
                    </div>
                    <div id="adminBenefits" style="display: none;">
                        <div class="benefit-item">
                            <i class="fas fa-chart-line"></i>
                            <div>
                                <h4>Analytics Dashboard</h4>
                                <p>Monitor bookings, revenue, and customer insights</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-bed"></i>
                            <div>
                                <h4>Room Management</h4>
                                <p>Manage room availability, pricing, and maintenance</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-users"></i>
                            <div>
                                <h4>Customer Management</h4>
                                <p>View and manage customer accounts and bookings</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-cog"></i>
                            <div>
                                <h4>System Settings</h4>
                                <p>Configure resort settings and employee access</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Error/Success Messages -->
    <div id="messageContainer" class="message-container"></div>

    <script src="assets/js/auth.js"></script>
    <script>
        // Role switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const clientBtn = document.getElementById('clientBtn');
            const adminBtn = document.getElementById('adminBtn');
            const loginType = document.getElementById('userType');
            const authBackground = document.getElementById('authBackground');
            const authCard = document.getElementById('authCard');
            const logoContainer = document.getElementById('logoContainer');
            const logoIcon = document.getElementById('logoIcon');
            const logoText = document.getElementById('logoText');
            
            // Role switching
            clientBtn.addEventListener('click', () => switchToClient());
            adminBtn.addEventListener('click', () => switchToAdmin());
            
            function switchToClient() {
                // Update active states
                clientBtn.classList.add('active');
                adminBtn.classList.remove('active');
                loginType.value = 'client';
                
                // Update themes
                authBackground.classList.remove('admin-theme');
                authCard.classList.remove('admin-theme');
                logoContainer.classList.remove('admin-theme');
                
                // Update content
                document.getElementById('pageTitle').textContent = 'Welcome Back!';
                document.getElementById('pageSubtitle').textContent = 'Sign in to your account to continue booking';
                document.getElementById('emailLabel').textContent = 'Email Address';
                document.getElementById('rememberText').textContent = 'Remember me';
                document.getElementById('submitText').innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
                document.getElementById('benefitsTitle').textContent = 'Why Book with Us?';
                
                // Show/hide sections
                document.getElementById('socialLoginSection').style.display = 'block';
                document.getElementById('securityNotice').style.display = 'none';
                document.getElementById('footerText').style.display = 'block';
                document.getElementById('supportText').style.display = 'none';
                document.getElementById('clientBenefits').style.display = 'block';
                document.getElementById('adminBenefits').style.display = 'none';
                
                // Update logo
                logoIcon.className = 'fas fa-palm-tree';
                logoText.textContent = 'Paradise Resort';
            }
            
            function switchToAdmin() {
                // Update active states
                adminBtn.classList.add('active');
                clientBtn.classList.remove('active');
                loginType.value = 'admin';
                
                // Update themes
                authBackground.classList.add('admin-theme');
                authCard.classList.add('admin-theme');
                logoContainer.classList.add('admin-theme');
                
                // Update content
                document.getElementById('pageTitle').textContent = 'Admin Login';
                document.getElementById('pageSubtitle').textContent = 'Access the resort management system';
                document.getElementById('emailLabel').textContent = 'Admin Email';
                document.getElementById('rememberText').textContent = 'Keep me signed in';
                document.getElementById('submitText').innerHTML = '<i class="fas fa-shield-alt"></i> Admin Sign In';
                document.getElementById('benefitsTitle').textContent = 'Admin Features';
                
                // Show/hide sections
                document.getElementById('socialLoginSection').style.display = 'none';
                document.getElementById('securityNotice').style.display = 'block';
                document.getElementById('footerText').style.display = 'none';
                document.getElementById('supportText').style.display = 'block';
                document.getElementById('clientBenefits').style.display = 'none';
                document.getElementById('adminBenefits').style.display = 'block';
                
                // Update logo
                logoIcon.className = 'fas fa-shield-alt';
                logoText.textContent = 'Paradise Resort Admin';
            }
            
            // Initialize based on URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const type = urlParams.get('type');
            if (type === 'admin') {
                switchToAdmin();
            } else {
                switchToClient();
            }
        });

        // Check for URL parameters (success/error messages)
        const urlParams = new URLSearchParams(window.location.search);
        const error = urlParams.get('error');
        const success = urlParams.get('success');

        if (error) {
            showMessage(getErrorMessage(error), 'error');
        }
        if (success) {
            showMessage(getSuccessMessage(success), 'success');
        }

        function getErrorMessage(error) {
            const errors = {
                'invalid_credentials': 'Invalid email or password. Please try again.',
                'user_banned': 'Your account has been suspended. Please contact support.',
                'access_denied': 'Access denied. Admin privileges required.',
                'session_expired': 'Your session has expired. Please login again.',
                'missing_fields': 'Please fill in all required fields.',
                'database_error': 'A system error occurred. Please try again later.',
                'login_required': 'Please login to access this page.',
                'rate_limit_exceeded': 'Too many login attempts. Please try again later.',
                'account_locked': 'Account temporarily locked due to multiple failed attempts. Please wait a few minutes or <a href="reset_login_lockout.php" style="color: #dc3545; text-decoration: underline;">reset lockout</a>.',
                'invalid_request': 'Invalid request method.',
                'invalid_token': 'Security token invalid. Please try again.',
                'invalid_user_type': 'Invalid user type selected.',
                'invalid_email': 'Please enter a valid email address.'
            };
            return errors[error] || 'An error occurred. Please try again.';
        }

        function getSuccessMessage(success) {
            const messages = {
                'registered': 'Registration successful! Please login with your credentials.',
                'logout': 'You have been logged out successfully.',
                'password_reset': 'Password reset link has been sent to your email.'
            };
            return messages[success] || 'Success!';
        }
    </script>
</body>
</html>
