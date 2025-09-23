// Authentication helper functions
let isUserLoggedIn = false;

// Check if user is logged in (check PHP session)
async function checkUserLoginStatus() {
    try {
        const response = await fetch('/RESORT/config/check_session.php');
        const data = await response.json();
        isUserLoggedIn = data.logged_in;
        updateNavigation(data);
        return data;
    } catch (error) {
        console.log('Session check error:', error);
        isUserLoggedIn = false;
        return { logged_in: false };
    }
}

// Require login function - shows modal if not logged in
function requireLogin() {
    if (!isUserLoggedIn) {
        showLoginModal();
        return false;
    }
    return true;
}

// Check login and redirect to booking
function checkLoginAndRedirect() {
    if (!isUserLoggedIn) {
        window.location.href = '/RESORT/login.php?redirect=booking';
    } else {
        window.location.href = '/RESORT/client/dashboard.php#book';
    }
}

// Show login modal
function showLoginModal() {
    const modal = document.getElementById('loginModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Add click event to overlay to close modal
        const overlay = modal.querySelector('.modal-overlay');
        if (overlay) {
            overlay.onclick = closeLoginModal;
        }
        
        // Add escape key listener
        document.addEventListener('keydown', handleEscapeKey);
    }
}

// Close login modal
function closeLoginModal() {
    const modal = document.getElementById('loginModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        document.removeEventListener('keydown', handleEscapeKey);
    }
}

// Handle escape key to close modal
function handleEscapeKey(event) {
    if (event.key === 'Escape') {
        closeLoginModal();
    }
}

// Initialize login status check on page load
window.addEventListener('load', function() {
    checkUserLoginStatus().then(data => {
        updateNavigationUI(data);
    });
});

// Update navigation based on login status
function updateNavigation(userData) {
    updateNavigationUI(userData);
}

// Update navigation UI based on login status
function updateNavigationUI(userData) {
    const authContainer = document.querySelector('.nav-auth');
    
    if (userData.logged_in && userData.customer_name) {
        // User is logged in - show user dropdown with dashboard and logout
        isUserLoggedIn = true;
        if (authContainer) {
            authContainer.innerHTML = `
                <div class="user-dropdown">
                    <button class="dropdown-toggle" id="userDropdownToggle">
                        <i class="fas fa-user-circle"></i>
                        <span>${userData.customer_name}</span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </button>
                    <div class="dropdown-menu" id="userDropdownMenu">
                        <a href="/RESORT/client/dashboard.php" class="dropdown-item">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="/RESORT/client/bookings.php" class="dropdown-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>My Bookings</span>
                        </a>
                        <a href="/RESORT/logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            `;
            
            // Initialize dropdown functionality
            initializeUserDropdown();
        }
    } else {
        // User not logged in - show login/register dropdown
        isUserLoggedIn = false;
        if (authContainer) {
            authContainer.innerHTML = `
                <div class="user-dropdown">
                    <button class="dropdown-toggle" id="userDropdownToggle">
                        <i class="fas fa-user-circle"></i>
                        <span>Account</span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </button>
                    <div class="dropdown-menu" id="userDropdownMenu">
                        <a href="/RESORT/login.php" class="dropdown-item">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Login</span>
                        </a>
                        <a href="/RESORT/register.php" class="dropdown-item">
                            <i class="fas fa-user-plus"></i>
                            <span>Sign Up</span>
                        </a>
                    </div>
                </div>
            `;
            
            // Initialize dropdown functionality
            initializeUserDropdown();
        }
    }
}

// Initialize user dropdown functionality
function initializeUserDropdown() {
    const dropdownToggle = document.getElementById('userDropdownToggle');
    const dropdownMenu = document.getElementById('userDropdownMenu');
    
    if (dropdownToggle && dropdownMenu) {
        // Toggle dropdown on button click
        dropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isOpen = dropdownMenu.classList.contains('show');
            
            if (isOpen) {
                closeDropdown();
            } else {
                openDropdown();
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!dropdownToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
                closeDropdown();
            }
        });
        
        // Close dropdown on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDropdown();
            }
        });
    }
    
    function openDropdown() {
        dropdownMenu.classList.add('show');
        dropdownToggle.classList.add('active');
        dropdownToggle.setAttribute('aria-expanded', 'true');
    }
    
    function closeDropdown() {
        dropdownMenu.classList.remove('show');
        dropdownToggle.classList.remove('active');
        dropdownToggle.setAttribute('aria-expanded', 'false');
    }
}

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize default dropdown (before login status is checked)
    initializeUserDropdown();
    
    // Mobile Navigation Toggle
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');

    if (hamburger && navMenu) {
        hamburger.addEventListener('click', function() {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
        });

        // Close mobile menu when clicking on a link
        document.querySelectorAll('.nav-link').forEach(n => n.addEventListener('click', () => {
            hamburger.classList.remove('active');
            navMenu.classList.remove('active');
        }));
    }

    // Navbar Scroll Effect
    const navbar = document.querySelector('.navbar');
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 100) {
            navbar.style.background = 'rgba(255, 255, 255, 0.99)';
            navbar.style.boxShadow = '0 2px 30px rgba(0, 0, 0, 0.15)';
            navbar.style.backdropFilter = 'blur(20px)';
        } else {
            navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
            navbar.style.backdropFilter = 'blur(15px)';
        }
    });

    // Smooth Scrolling for Navigation Links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const offsetTop = target.offsetTop - 80; // Account for fixed navbar
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Scroll Animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe elements for animation
    const animateElements = document.querySelectorAll('.room-card, .amenity-card, .service-card, .gallery-item');
    animateElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });

    // Stats Counter Animation
    const statsSection = document.querySelector('.about-stats');
    let statsAnimated = false;

    const statsObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting && !statsAnimated) {
                statsAnimated = true;
                animateStats();
            }
        });
    }, { threshold: 0.5 });

    if (statsSection) {
        statsObserver.observe(statsSection);
    }

    function animateStats() {
        const statNumbers = document.querySelectorAll('.stat h3');
        statNumbers.forEach(stat => {
            const originalText = stat.textContent.trim();
            
            // Special handling for 24/7 - animate the 24 but keep the /7
            if (originalText === '24/7') {
                let currentNumber = 0;
                const finalNumber = 24;
                const increment = finalNumber / 50; // Slower animation for 24/7
                
                const updateStats = setInterval(() => {
                    currentNumber += increment;
                    if (currentNumber >= finalNumber) {
                        stat.textContent = '24/7';
                        clearInterval(updateStats);
                    } else {
                        stat.textContent = Math.floor(currentNumber) + '/7';
                    }
                }, 30);
                return;
            }
            
            // Skip elements with data-no-animate attribute
            if (stat.hasAttribute('data-no-animate')) {
                return;
            }
            
            // Skip animation for other special characters (but not 24/7 which we handle above)
            if (originalText.includes('-') || originalText.match(/[a-zA-Z].*[0-9]|[0-9].*[a-zA-Z]/)) {
                return;
            }
            
            const finalNumber = parseInt(stat.textContent.replace(/\D/g, ''));
            const suffix = stat.textContent.replace(/[0-9]/g, '');
            
            // Only animate if we have a valid number
            if (finalNumber && finalNumber > 0) {
                let currentNumber = 0;
                const increment = finalNumber / 100;
                
                const updateStats = setInterval(() => {
                    currentNumber += increment;
                    if (currentNumber >= finalNumber) {
                        stat.textContent = finalNumber + suffix;
                        clearInterval(updateStats);
                    } else {
                        stat.textContent = Math.floor(currentNumber) + suffix;
                    }
                }, 20);
            }
        });
    }

    // Gallery Lightbox Effect
    const galleryItems = document.querySelectorAll('.gallery-item');
    galleryItems.forEach((item, index) => {
        item.addEventListener('click', function() {
            // Create lightbox overlay
            const overlay = document.createElement('div');
            overlay.className = 'lightbox-overlay';
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.9);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
                cursor: pointer;
            `;
            
            // Create image element
            const img = document.createElement('img');
            const originalImg = item.querySelector('img');
            if (originalImg) {
                img.src = originalImg.src;
                img.alt = originalImg.alt;
            } else {
                img.alt = 'Gallery Image ' + (index + 1);
            }
            img.style.cssText = `
                max-width: 90%;
                max-height: 90%;
                border-radius: 10px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            `;
            
            overlay.appendChild(img);
            document.body.appendChild(overlay);
            
            // Close lightbox when clicking overlay
            overlay.addEventListener('click', function() {
                document.body.removeChild(overlay);
            });
            
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
            
            // Restore body scroll when closed
            overlay.addEventListener('click', function() {
                document.body.style.overflow = 'auto';
            });
        });
    });

    // Contact Form Handling
    const contactForm = document.querySelector('.contact-form form');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(contactForm);
            const name = contactForm.querySelector('input[placeholder="Your Name"]').value;
            const email = contactForm.querySelector('input[placeholder="Your Email"]').value;
            const phone = contactForm.querySelector('input[placeholder="Your Phone"]').value;
            const message = contactForm.querySelector('textarea[placeholder="Your Message"]').value;
            
            // Basic validation
            if (!name || !email || !message) {
                showNotification('Please fill in all required fields.', 'error');
                return;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showNotification('Please enter a valid email address.', 'error');
                return;
            }
            
            // Simulate form submission
            showNotification('Thank you for your message! We\'ll get back to you soon.', 'success');
            contactForm.reset();
        });
    }

    // Notification System
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            background: ${type === 'success' ? '#4a9960' : type === 'error' ? '#ff6b6b' : '#2c5530'};
            color: white;
            padding: 15px 25px;
            border-radius: 5px;
            z-index: 10000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            max-width: 300px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        `;
        
        document.body.appendChild(notification);
        
        // Slide in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Remove after 5 seconds
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 5000);
    }

    // Parallax Effect for Hero Section
    const hero = document.querySelector('.hero');
    if (hero) {
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const rate = scrolled * -0.5;
            hero.style.transform = `translateY(${rate}px)`;
        });
    }

    // Booking Button Interactions
    const bookingButtons = document.querySelectorAll('a[href*="booking"]');
    bookingButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!button.href.includes('.php')) {
                e.preventDefault();
                showNotification('Booking system will be available soon! Please contact us directly.', 'info');
            }
        });
    });

    // Service Buttons
    const serviceButtons = document.querySelectorAll('.service-card .btn');
    serviceButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const serviceName = this.closest('.service-card').querySelector('h3').textContent;
            showNotification(`${serviceName} details will be available soon! Please contact us for more information.`, 'info');
        });
    });

    // Add loading animation
    window.addEventListener('load', function() {
        document.body.classList.add('loaded');
    });

    // Intersection Observer for fade-in animations
    const fadeElements = document.querySelectorAll('.about-text, .contact-info, .contact-form');
    
    const fadeObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateX(0)';
            }
        });
    }, { threshold: 0.2 });

    fadeElements.forEach((el, index) => {
        el.style.opacity = '0';
        el.style.transform = index % 2 === 0 ? 'translateX(-30px)' : 'translateX(30px)';
        el.style.transition = 'all 0.8s ease';
        fadeObserver.observe(el);
    });

    // Dynamic year in footer
    const currentYear = new Date().getFullYear();
    const footerText = document.querySelector('.footer-bottom p');
    if (footerText) {
        footerText.innerHTML = footerText.innerHTML.replace('2025', currentYear);
    }

    // Add hover effects to cards
    const cards = document.querySelectorAll('.room-card, .amenity-card, .service-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
});

// Utility Functions
function scrollToSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (section) {
        const offsetTop = section.offsetTop - 80;
        window.scrollTo({
            top: offsetTop,
            behavior: 'smooth'
        });
    }
}

// Page Visibility API for performance
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // Pause animations or reduce activity
        document.querySelectorAll('.animate-on-scroll').forEach(el => {
            el.style.animationPlayState = 'paused';
        });
    } else {
        // Resume animations
        document.querySelectorAll('.animate-on-scroll').forEach(el => {
            el.style.animationPlayState = 'running';
        });
    }
});

// Preload images for better performance
function preloadImages() {
    const imageUrls = [
        'assets/images/resort-exterior.jpg',
        'assets/images/deluxe-room.jpg',
        'assets/images/pool-room.jpg',
        'assets/images/family-room.jpg',
        'assets/images/gallery-1.jpg',
        'assets/images/gallery-2.jpg',
        'assets/images/gallery-3.jpg',
        'assets/images/gallery-4.jpg',
        'assets/images/gallery-5.jpg',
        'assets/images/gallery-6.jpg'
    ];
    
    imageUrls.forEach(url => {
        const img = new Image();
        img.src = url;
    });
}

// Call preload on page load
window.addEventListener('load', preloadImages);
