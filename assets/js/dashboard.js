// Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize dashboard components
    initializeUserDropdown();
    loadDashboardStats();
    setupAnimations();

});

// User dropdown functionality
function initializeUserDropdown() {
    const dropdownBtn = document.querySelector('.user-dropdown-btn');
    const dropdownMenu = document.querySelector('.dropdown-menu');
    
    if (!dropdownBtn || !dropdownMenu) return;
    
    dropdownBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdownMenu.classList.toggle('show');
        
        // Rotate chevron
        const chevron = dropdownBtn.querySelector('.fa-chevron-down');
        if (chevron) {
            chevron.style.transform = dropdownMenu.classList.contains('show') ? 
                'rotate(180deg)' : 'rotate(0deg)';
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
        dropdownMenu.classList.remove('show');
        const chevron = dropdownBtn.querySelector('.fa-chevron-down');
        if (chevron) {
            chevron.style.transform = 'rotate(0deg)';
        }
    });
}

// Load and animate dashboard statistics
function loadDashboardStats() {
    const statElements = {
        totalBookings: document.getElementById('totalBookings'),
        upcomingBookings: document.getElementById('upcomingBookings'),
        loyaltyPoints: document.getElementById('loyaltyPoints'),
        totalSpent: document.getElementById('totalSpent')
    };
    
    // Simulate API call delay
    setTimeout(() => {
        // Animate counting for each stat
        if (statElements.totalBookings) {
            animateCount(statElements.totalBookings, 0, 8, 1000);
        }
        
        if (statElements.upcomingBookings) {
            animateCount(statElements.upcomingBookings, 0, 2, 800);
        }
        
        if (statElements.loyaltyPoints) {
            animateCount(statElements.loyaltyPoints, 0, 2450, 1500, true);
        }
        
        if (statElements.totalSpent) {
            animateCount(statElements.totalSpent, 0, 18500, 1200, true, '₱');
        }
        
        // Load recent bookings after stats
        setTimeout(loadRecentBookings, 500);
        
    }, 500);
}

// Animate counting numbers
function animateCount(element, start, end, duration, addCommas = false, prefix = '') {
    const startTime = performance.now();
    const range = end - start;
    
    function updateCount(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        const easeOutCubic = 1 - Math.pow(1 - progress, 3);
        const currentValue = Math.floor(start + (range * easeOutCubic));
        
        let displayValue = currentValue.toString();
        if (addCommas) {
            displayValue = currentValue.toLocaleString();
        }
        
        element.textContent = prefix + displayValue;
        
        if (progress < 1) {
            requestAnimationFrame(updateCount);
        } else {
            element.textContent = prefix + (addCommas ? end.toLocaleString() : end);
        }
    }
    
    requestAnimationFrame(updateCount);
}

// Load recent bookings
function loadRecentBookings() {
    const bookingsContainer = document.getElementById('recentBookings');
    if (!bookingsContainer) return;
    
    // Simulate API call
    setTimeout(() => {
        // Sample booking data (in real app, this would come from server)
        const bookings = [
            {
                id: 'BK-2025-001',
                roomType: 'Deluxe Room',
                checkIn: '2025-10-15',
                checkOut: '2025-10-18',
                status: 'confirmed',
                amount: '₱7,500'
            },
            {
                id: 'BK-2025-002',
                roomType: 'Pool Room',
                checkIn: '2025-11-02',
                checkOut: '2025-11-04',
                status: 'pending',
                amount: '₱3,000'
            }
        ];
        
        renderBookings(bookings, bookingsContainer);
    }, 800);
}

// Render bookings list
function renderBookings(bookings, container) {
    if (bookings.length === 0) {
        container.innerHTML = `
            <div class="empty-state animate-fade-in">
                <i class="fas fa-calendar-times"></i>
                <h3>No bookings yet</h3>
                <p>Start planning your perfect getaway!</p>
                <a href="book_room.php" class="btn btn-primary">Book Your First Stay</a>
            </div>
        `;
        return;
    }
    
    const bookingsHTML = bookings.map((booking, index) => {
        const checkInDate = new Date(booking.checkIn).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric'
        });
        const checkOutDate = new Date(booking.checkOut).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric'
        });
        
        return `
            <div class="booking-card animate-slide-in" style="animation-delay: ${index * 0.1}s;">
                <div class="booking-info">
                    <div class="booking-header">
                        <h4>${booking.roomType}</h4>
                        <span class="booking-id">#${booking.id}</span>
                    </div>
                    <div class="booking-details">
                        <p><i class="fas fa-calendar"></i> ${checkInDate} - ${checkOutDate}</p>
                        <p><i class="fas fa-dollar-sign"></i> ${booking.amount}</p>
                    </div>
                </div>
                <div class="booking-status">
                    <span class="status-badge ${booking.status}">
                        ${booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}
                    </span>
                </div>
            </div>
        `;
    }).join('');
    
    container.innerHTML = bookingsHTML;
}

// Setup animations
function setupAnimations() {
    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-visible');
            }
        });
    }, observerOptions);
    
    // Observe stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = `all 0.6s ease ${index * 0.1}s`;
        observer.observe(card);
    });
    
    // Observe room cards
    const roomCards = document.querySelectorAll('.room-type-card');
    roomCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = `all 0.6s ease ${index * 0.2}s`;
        observer.observe(card);
    });
}

// Add CSS classes for animations
const style = document.createElement('style');
style.textContent = `
    .animate-visible {
        opacity: 1 !important;
        transform: translateY(0) !important;
    }
    
    .animate-fade-in {
        animation: fadeIn 0.6s ease-out;
    }
    
    .animate-slide-in {
        animation: slideInLeft 0.6s ease-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes slideInLeft {
        from { opacity: 0; transform: translateX(-30px); }
        to { opacity: 1; transform: translateX(0); }
    }
`;
document.head.appendChild(style);

// Utility functions
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas ${getNotificationIcon(type)}"></i>
        <span>${message}</span>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        border-left: 4px solid ${getNotificationColor(type)};
        display: flex;
        align-items: center;
        gap: 1rem;
        z-index: 10000;
        max-width: 400px;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Slide in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto remove
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 300);
    }, 5000);
}

function getNotificationIcon(type) {
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    return icons[type] || icons.info;
}

function getNotificationColor(type) {
    const colors = {
        success: '#4a9960',
        error: '#ff6b6b',
        warning: '#ffa500',
        info: '#2196f3'
    };
    return colors[type] || colors.info;
}

// Navigation active link update
function updateActiveNavLink() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });
}

// Call on page load
updateActiveNavLink();
