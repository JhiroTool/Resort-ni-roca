// Authentication JavaScript
document.addEventListener('DOMContentLoaded', function() {
    
    // Password toggle functionality
    const passwordToggles = document.querySelectorAll('.password-toggle');
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const passwordInput = this.parentElement.querySelector('input[type="password"], input[type="text"]');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Form validation
    const forms = document.querySelectorAll('.auth-form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
            } else {
                showLoader(form);
            }
        });
    });

    // Real-time validation
    const inputs = document.querySelectorAll('.auth-form input');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });

        input.addEventListener('input', function() {
            clearError(this);
        });
    });

    // Login form specific validation
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const email = this.querySelector('#email').value;
            const password = this.querySelector('#password').value;

            if (!email || !password) {
                e.preventDefault();
                showMessage('Please fill in all fields', 'error');
            }
        });
    }

    // Register form specific validation
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const password = this.querySelector('#password').value;
            const confirmPassword = this.querySelector('#confirmPassword').value;
            const termsAccepted = this.querySelector('input[name="terms_accepted"]').checked;

            if (password !== confirmPassword) {
                e.preventDefault();
                showError('confirmPassword', 'Passwords do not match');
                showMessage('Passwords do not match', 'error');
                return;
            }

            if (!termsAccepted) {
                e.preventDefault();
                showMessage('You must accept the terms and conditions', 'error');
                return;
            }

            if (!isPasswordStrong(password)) {
                e.preventDefault();
                showError('password', 'Password is too weak');
                showMessage('Please use a stronger password', 'error');
                return;
            }
        });

        // Phone number formatting
        const phoneInput = registerForm.querySelector('#phone');
        if (phoneInput) {
            phoneInput.addEventListener('input', function() {
                // Remove non-digits
                let value = this.value.replace(/\D/g, '');
                
                // Limit to 15 digits (international format)
                if (value.length > 15) {
                    value = value.slice(0, 15);
                }
                
                this.value = value;
            });
        }
    }

    // Social login buttons (placeholder functionality)
    const socialButtons = document.querySelectorAll('.social-btn');
    socialButtons.forEach(button => {
        button.addEventListener('click', function() {
            const provider = this.classList.contains('google-btn') ? 'Google' : 'Facebook';
            showMessage(`${provider} login is coming soon!`, 'info');
        });
    });
});

// Validation functions
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required]');
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(input) {
    const value = input.value.trim();
    const fieldName = input.name;
    let isValid = true;

    // Clear previous errors
    clearError(input);

    // Required field validation
    if (input.hasAttribute('required') && !value) {
        showError(input.id || fieldName, 'This field is required');
        return false;
    }

    // Email validation
    if (input.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            showError(input.id || fieldName, 'Please enter a valid email address');
            isValid = false;
        }
    }

    // Phone validation
    if (input.type === 'tel' && value) {
        const phoneRegex = /^\d{10,15}$/;
        if (!phoneRegex.test(value)) {
            showError(input.id || fieldName, 'Please enter a valid phone number (10-15 digits)');
            isValid = false;
        }
    }

    // Password validation
    if (input.type === 'password' && input.name === 'password' && value) {
        if (value.length < 8) {
            showError(input.id || fieldName, 'Password must be at least 8 characters long');
            isValid = false;
        }
    }

    // Name validation
    if ((fieldName === 'first_name' || fieldName === 'last_name') && value) {
        const nameRegex = /^[a-zA-Z\s]+$/;
        if (!nameRegex.test(value)) {
            showError(input.id || fieldName, 'Names can only contain letters and spaces');
            isValid = false;
        }
    }

    return isValid;
}

function showError(fieldId, message) {
    const errorElement = document.getElementById(fieldId + '-error');
    const inputElement = document.getElementById(fieldId);
    
    if (errorElement) {
        errorElement.textContent = message;
    }
    
    if (inputElement) {
        inputElement.classList.add('error');
    }
}

function clearError(input) {
    const errorElement = document.getElementById(input.id + '-error');
    
    if (errorElement) {
        errorElement.textContent = '';
    }
    
    input.classList.remove('error');
}

function isPasswordStrong(password) {
    // Check for at least 8 characters, one uppercase, one lowercase, one number
    const hasLength = password.length >= 8;
    const hasUpper = /[A-Z]/.test(password);
    const hasLower = /[a-z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    
    return hasLength && hasUpper && hasLower && hasNumber;
}

// UI Helper functions
function showLoader(form) {
    const submitBtn = form.querySelector('button[type="submit"]');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoader = submitBtn.querySelector('.btn-loader');
    
    if (btnText && btnLoader) {
        btnText.style.display = 'none';
        btnLoader.style.display = 'flex';
        submitBtn.disabled = true;
    }
}

function hideLoader(form) {
    const submitBtn = form.querySelector('button[type="submit"]');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoader = submitBtn.querySelector('.btn-loader');
    
    if (btnText && btnLoader) {
        btnText.style.display = 'block';
        btnLoader.style.display = 'none';
        submitBtn.disabled = false;
    }
}

function showMessage(message, type = 'info') {
    const container = document.getElementById('messageContainer');
    if (!container) return;

    const messageEl = document.createElement('div');
    messageEl.className = `message ${type}`;
    
    const icon = getMessageIcon(type);
    messageEl.innerHTML = `
        <i class="${icon}"></i>
        <div>
            <div style="font-weight: 600;">${getMessageTitle(type)}</div>
            <div style="font-size: 0.9rem; opacity: 0.8;">${message}</div>
        </div>
    `;
    
    container.appendChild(messageEl);
    
    // Trigger animation
    setTimeout(() => {
        messageEl.classList.add('show');
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        messageEl.classList.remove('show');
        setTimeout(() => {
            if (messageEl.parentNode) {
                container.removeChild(messageEl);
            }
        }, 300);
    }, 5000);
    
    // Click to dismiss
    messageEl.addEventListener('click', function() {
        this.classList.remove('show');
        setTimeout(() => {
            if (this.parentNode) {
                container.removeChild(this);
            }
        }, 300);
    });
}

function getMessageIcon(type) {
    const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
    };
    return icons[type] || icons.info;
}

function getMessageTitle(type) {
    const titles = {
        success: 'Success!',
        error: 'Error!',
        warning: 'Warning!',
        info: 'Info'
    };
    return titles[type] || titles.info;
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Form auto-save (for better UX)
function setupFormAutoSave(formId, storageKey) {
    const form = document.getElementById(formId);
    if (!form) return;

    const inputs = form.querySelectorAll('input:not([type="password"]):not([type="checkbox"])');
    
    // Load saved data
    const savedData = localStorage.getItem(storageKey);
    if (savedData) {
        const data = JSON.parse(savedData);
        inputs.forEach(input => {
            if (data[input.name] && input.type !== 'password') {
                input.value = data[input.name];
            }
        });
    }

    // Save data on input
    const saveData = debounce(() => {
        const formData = {};
        inputs.forEach(input => {
            if (input.value && input.type !== 'password') {
                formData[input.name] = input.value;
            }
        });
        localStorage.setItem(storageKey, JSON.stringify(formData));
    }, 1000);

    inputs.forEach(input => {
        input.addEventListener('input', saveData);
    });

    // Clear saved data on successful submission
    form.addEventListener('submit', function() {
        setTimeout(() => {
            localStorage.removeItem(storageKey);
        }, 1000);
    });
}

// Initialize auto-save for register form
setupFormAutoSave('registerForm', 'resort_register_form_data');

// Handle browser back button
window.addEventListener('popstate', function(e) {
    // Clear any loaders if user navigates back
    const forms = document.querySelectorAll('.auth-form');
    forms.forEach(hideLoader);
});

// Accessibility improvements
document.addEventListener('keydown', function(e) {
    // ESC key to close messages
    if (e.key === 'Escape') {
        const messages = document.querySelectorAll('.message.show');
        messages.forEach(message => {
            message.classList.remove('show');
            setTimeout(() => {
                if (message.parentNode) {
                    message.parentNode.removeChild(message);
                }
            }, 300);
        });
    }
});

// Handle form submission errors from server
window.addEventListener('load', function() {
    // Clear loaders on page load (in case of redirect)
    const forms = document.querySelectorAll('.auth-form');
    forms.forEach(hideLoader);
});
