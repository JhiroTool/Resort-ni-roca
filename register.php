<?php
session_start();

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if user is already logged in
if (isset($_SESSION['customer_id'])) {
    header("Location: client/dashboard.php");
    exit();
}
?>
<?php
session_start();

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if user is already logged in
if (isset($_SESSION['customer_id'])) {
    header("Location: client/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Paradise Resort</title>
    <link rel="stylesheet" href="assets/css/auth.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-background">
            <div class="auth-overlay"></div>
        </div>
        
        <div class="auth-content">
            <div class="auth-header">
                <a href="index.php" class="back-home">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Home</span>
                </a>
                <div class="logo">
                    <i class="fas fa-palm-tree"></i>
                    <h1>Paradise Resort</h1>
                </div>
            </div>

            <div class="auth-form-container register-container">
                <div class="auth-card">
                    <div class="auth-card-header">
                        <h2>Join Paradise Resort</h2>
                        <p>Create your account to start booking amazing experiences</p>
                    </div>

                    <form id="registerForm" class="auth-form" action="auth/register_handler.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="form-row">
                            <div class="form-group half-width">
                                <label for="firstName">
                                    <i class="fas fa-user"></i>
                                    First Name
                                </label>
                                <input type="text" id="firstName" name="first_name" required placeholder="Enter first name">
                                <span class="error-message" id="firstName-error"></span>
                            </div>

                            <div class="form-group half-width">
                                <label for="lastName">
                                    <i class="fas fa-user"></i>
                                    Last Name
                                </label>
                                <input type="text" id="lastName" name="last_name" required placeholder="Enter last name">
                                <span class="error-message" id="lastName-error"></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i>
                                Email Address
                            </label>
                            <input type="email" id="email" name="email" required placeholder="Enter your email">
                            <span class="error-message" id="email-error"></span>
                        </div>

                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i>
                                Phone Number
                            </label>
                            <input type="tel" id="phone" name="phone" required placeholder="Enter your phone number" pattern="[0-9]{10,15}">
                            <span class="error-message" id="phone-error"></span>
                        </div>

                        <div class="form-group">
                            <label for="password">
                                <i class="fas fa-lock"></i>
                                Password
                            </label>
                            <div class="password-input">
                                <input type="password" id="password" name="password" required placeholder="Create a password">
                                <button type="button" class="password-toggle" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength" id="passwordStrength"></div>
                            <span class="error-message" id="password-error"></span>
                        </div>

                        <div class="form-group">
                            <label for="confirmPassword">
                                <i class="fas fa-lock"></i>
                                Confirm Password
                            </label>
                            <div class="password-input">
                                <input type="password" id="confirmPassword" name="confirm_password" required placeholder="Confirm your password">
                                <button type="button" class="password-toggle" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <span class="error-message" id="confirmPassword-error"></span>
                        </div>

                        <div class="form-options">
                            <label class="checkbox-container">
                                <input type="checkbox" name="terms_accepted" required>
                                <span class="checkmark"></span>
                                I agree to the <a href="terms.php" target="_blank">Terms & Conditions</a> and <a href="privacy.php" target="_blank">Privacy Policy</a>
                            </label>
                        </div>

                        <div class="form-options">
                            <label class="checkbox-container">
                                <input type="checkbox" name="newsletter_subscription">
                                <span class="checkmark"></span>
                                Subscribe to our newsletter for special offers and updates
                            </label>
                        </div>

                        <button type="submit" class="auth-btn auth-btn-primary">
                            <span class="btn-text">Create Account</span>
                            <div class="btn-loader" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </button>

                        <div class="auth-divider">
                            <span>or</span>
                        </div>

                        <div class="social-login">
                            <button type="button" class="social-btn google-btn">
                                <i class="fab fa-google"></i>
                                Sign up with Google
                            </button>
                            <button type="button" class="social-btn facebook-btn">
                                <i class="fab fa-facebook-f"></i>
                                Sign up with Facebook
                            </button>
                        </div>
                    </form>

                    <div class="auth-footer">
                        <p>Already have an account? <a href="login.php" class="auth-link">Sign in here</a></p>
                    </div>
                </div>

                <div class="auth-benefits">
                    <h3>Member Benefits</h3>
                    <div class="benefit-item">
                        <i class="fas fa-star"></i>
                        <div>
                            <h4>Exclusive Deals</h4>
                            <p>Access to member-only discounts and promotions</p>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-history"></i>
                        <div>
                            <h4>Booking History</h4>
                            <p>Keep track of all your past and future reservations</p>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-bell"></i>
                        <div>
                            <h4>Instant Notifications</h4>
                            <p>Get updates about your bookings and special offers</p>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-headset"></i>
                        <div>
                            <h4>Priority Support</h4>
                            <p>Faster response times for member inquiries</p>
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
        // Check for URL parameters (error messages)
        const urlParams = new URLSearchParams(window.location.search);
        const error = urlParams.get('error');

        if (error) {
            showMessage(getErrorMessage(error), 'error');
        }

        function getErrorMessage(error) {
            const errors = {
                'email_exists': 'An account with this email already exists. Please login instead.',
                'phone_exists': 'An account with this phone number already exists.',
                'password_mismatch': 'Passwords do not match. Please try again.',
                'weak_password': 'Password is too weak. Please use a stronger password.',
                'missing_fields': 'Please fill in all required fields.',
                'invalid_email': 'Please enter a valid email address.',
                'invalid_phone': 'Please enter a valid phone number.',
                'database_error': 'A system error occurred. Please try again later.',
                'terms_required': 'You must accept the terms and conditions to register.'
            };
            return errors[error] || 'An error occurred. Please try again.';
        }

        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });

        function checkPasswordStrength(password) {
            const strengthIndicator = document.getElementById('passwordStrength');
            let strength = 0;
            let feedback = '';

            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            switch (strength) {
                case 0:
                case 1:
                    strengthIndicator.className = 'password-strength weak';
                    feedback = 'Very weak';
                    break;
                case 2:
                    strengthIndicator.className = 'password-strength weak';
                    feedback = 'Weak';
                    break;
                case 3:
                    strengthIndicator.className = 'password-strength medium';
                    feedback = 'Medium';
                    break;
                case 4:
                    strengthIndicator.className = 'password-strength strong';
                    feedback = 'Strong';
                    break;
                case 5:
                    strengthIndicator.className = 'password-strength very-strong';
                    feedback = 'Very strong';
                    break;
            }

            strengthIndicator.textContent = password.length > 0 ? `Password strength: ${feedback}` : '';
        }
    </script>
</body>
</html>
