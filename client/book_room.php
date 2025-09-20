<?php
/**
 * Room Booking Page
 * Paradise Resort - Guest Accommodation System
 */

require_once '../config/database.php';

// Start secure session
startSecureSession();

// Require login
requireLogin();

// Get customer information
$customer = DatabaseUtilities::getCustomerById($_SESSION['cust_id']);

if (!$customer) {
    logout();
    header('Location: ../login.php?error=session_expired');
    exit();
}

// Get available rooms
$query = "SELECT * FROM room WHERE Room_Status = 'Available' ORDER BY Room_Rate ASC";
$rooms = executeQuery($query);

// Get amenities
$amenityQuery = "SELECT * FROM amenity ORDER BY Amenity_Cost ASC";
$amenities = executeQuery($amenityQuery);

// Get services
$serviceQuery = "SELECT * FROM service ORDER BY Service_Cost ASC";
$services = executeQuery($serviceQuery);

// Get room type if specified in URL
$selectedRoomType = isset($_GET['type']) ? $_GET['type'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Room - Paradise Resort</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/booking.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar dashboard-nav">
        <div class="nav-container">
            <div class="nav-logo">
                <h2><i class="fas fa-palm-tree"></i> Paradise Resort</h2>
            </div>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="bookings.php" class="nav-link">My Bookings</a>
                <a href="profile.php" class="nav-link">Profile</a>
                <a href="../index.php" class="nav-link">Home</a>
                <div class="user-dropdown">
                    <button class="user-dropdown-btn">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($customer['Cust_FN']); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                        <div class="dropdown-divider"></div>
                        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Booking Content -->
    <div class="booking-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="container">
                <div class="header-content">
                    <div class="breadcrumb">
                        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                        <span><i class="fas fa-chevron-right"></i></span>
                        <span>Book Room</span>
                    </div>
                    <h1><i class="fas fa-calendar-plus"></i> Book Your Perfect Stay</h1>
                    <p>Choose your room, dates, and extras for an unforgettable experience</p>
                </div>
            </div>
        </div>

        <!-- Booking Form -->
        <div class="booking-form-section">
            <div class="container">
                <form id="bookingForm" class="booking-form" action="process_booking.php" method="POST">
                    <div class="form-grid">
                        <!-- Room Selection -->
                        <div class="form-section">
                            <h3><i class="fas fa-bed"></i> Choose Your Room</h3>
                            <div class="room-cards">
                                <?php foreach ($rooms as $room): ?>
                                <div class="room-card <?php echo (strtolower($room['Room_Type']) === strtolower($selectedRoomType)) ? 'selected' : ''; ?>" 
                                     data-room-id="<?php echo $room['Room_ID']; ?>" 
                                     data-room-rate="<?php echo $room['Room_Rate']; ?>"
                                     data-room-capacity="<?php echo $room['Room_Cap']; ?>">
                                    <div class="room-image">
                                        <img src="../assets/images/<?php echo strtolower(str_replace(' ', '-', $room['Room_Type'])); ?>-room.jpg" 
                                             alt="<?php echo $room['Room_Type']; ?>" 
                                             onerror="this.src='../assets/images/default-room.jpg'">
                                        <div class="room-overlay">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </div>
                                    <div class="room-details">
                                        <h4><?php echo htmlspecialchars($room['Room_Type']); ?></h4>
                                        <p class="room-capacity">
                                            <i class="fas fa-users"></i> Up to <?php echo $room['Room_Cap']; ?> guests
                                        </p>
                                        <p class="room-price">
                                            ₱<?php echo number_format($room['Room_Rate'], 2); ?> <span>per night</span>
                                        </p>
                                        <div class="room-features">
                                            <span class="feature"><i class="fas fa-wifi"></i> Free WiFi</span>
                                            <span class="feature"><i class="fas fa-tv"></i> Smart TV</span>
                                            <span class="feature"><i class="fas fa-snowflake"></i> AC</span>
                                        </div>
                                    </div>
                                    <input type="radio" name="room_id" value="<?php echo $room['Room_ID']; ?>" 
                                           <?php echo (strtolower($room['Room_Type']) === strtolower($selectedRoomType)) ? 'checked' : ''; ?>>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Booking Details -->
                        <div class="form-section">
                            <h3><i class="fas fa-calendar-alt"></i> Booking Details</h3>
                            <div class="booking-details-form">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="checkin">Check-in Date</label>
                                        <input type="date" id="checkin" name="checkin" required 
                                               min="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="checkout">Check-out Date</label>
                                        <input type="date" id="checkout" name="checkout" required 
                                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="guests">Number of Guests</label>
                                        <select id="guests" name="guests" required>
                                            <option value="">Select guests</option>
                                            <?php for ($i = 1; $i <= 20; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?> Guest<?php echo $i > 1 ? 's' : ''; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Duration</label>
                                        <div class="duration-display">
                                            <span id="nightCount">0</span> night(s)
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Amenities -->
                        <div class="form-section">
                            <h3><i class="fas fa-star"></i> Add Amenities</h3>
                            <p style="color: #666; margin-bottom: 1.5rem; font-size: 0.95rem;">
                                <i class="fas fa-info-circle" style="color: #4a9960;"></i> 
                                Click on the amenities you'd like to add to your booking
                            </p>
                            <div class="amenities-grid">
                                <?php foreach ($amenities as $amenity): ?>
                                <div class="amenity-card" data-amenity-id="<?php echo $amenity['Amenity_ID']; ?>">
                                    <div class="amenity-info">
                                        <div class="amenity-icon">
                                            <i class="fas fa-<?php 
                                                echo match(strtolower($amenity['Amenity_Name'])) {
                                                    'wi-fi' => 'wifi',
                                                    'breakfast' => 'utensils',
                                                    'spa' => 'spa',
                                                    'atv' => 'motorcycle',
                                                    default => 'star'
                                                };
                                            ?>"></i>
                                        </div>
                                        <div class="amenity-details">
                                            <h5><?php echo htmlspecialchars($amenity['Amenity_Name']); ?></h5>
                                            <p><?php echo htmlspecialchars($amenity['Amenity_Desc']); ?></p>
                                            <span class="amenity-price">₱<?php echo number_format($amenity['Amenity_Cost'], 2); ?></span>
                                        </div>
                                    </div>
                                    <div class="selection-indicator">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <label class="amenity-checkbox">
                                        <input type="checkbox" name="amenities[]" value="<?php echo $amenity['Amenity_ID']; ?>" 
                                               data-price="<?php echo $amenity['Amenity_Cost']; ?>">
                                        <span class="checkmark"></span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Services -->
                        <div class="form-section">
                            <h3><i class="fas fa-concierge-bell"></i> Additional Services</h3>
                            <p style="color: #666; margin-bottom: 1.5rem; font-size: 0.95rem;">
                                <i class="fas fa-info-circle" style="color: #4a9960;"></i> 
                                Select additional services to enhance your stay
                            </p>
                            <div class="services-grid">
                                <?php foreach ($services as $service): ?>
                                <div class="service-card" data-service-id="<?php echo $service['Service_ID']; ?>">
                                    <div class="service-info">
                                        <div class="service-icon">
                                            <i class="fas fa-car"></i>
                                        </div>
                                        <div class="service-details">
                                            <h5><?php echo htmlspecialchars($service['Service_Name']); ?></h5>
                                            <p><?php echo htmlspecialchars($service['Service_Desc']); ?></p>
                                            <span class="service-price">₱<?php echo number_format($service['Service_Cost'], 2); ?></span>
                                        </div>
                                    </div>
                                    <div class="selection-indicator">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <label class="service-checkbox">
                                        <input type="checkbox" name="services[]" value="<?php echo $service['Service_ID']; ?>" 
                                               data-price="<?php echo $service['Service_Cost']; ?>">
                                        <span class="checkmark"></span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Summary -->
                    <div class="booking-summary">
                        <h3><i class="fas fa-receipt"></i> Booking Summary</h3>
                        <div class="summary-content">
                            <div class="summary-item">
                                <span>Selected Room:</span>
                                <span id="selectedRoom">Not selected</span>
                            </div>
                            <div class="summary-item">
                                <span>Duration:</span>
                                <span id="summaryDuration">0 nights</span>
                            </div>
                            <div class="summary-item">
                                <span>Room Cost:</span>
                                <span id="roomCost">₱0.00</span>
                            </div>
                            <div class="summary-item">
                                <span>Amenities:</span>
                                <span id="amenitiesCost">₱0.00</span>
                            </div>
                            <div class="summary-item">
                                <span>Services:</span>
                                <span id="servicesCost">₱0.00</span>
                            </div>
                            <hr>
                            <div class="summary-total">
                                <span>Total Cost:</span>
                                <span id="totalCost">₱0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="history.back()">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <button type="submit" class="btn btn-primary" id="bookNowBtn" disabled>
                            <i class="fas fa-credit-card"></i> Book Now
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/booking.js"></script>
    <script>
        // Initialize booking form
        document.addEventListener('DOMContentLoaded', function() {
            initializeBookingForm();
        });

        function initializeBookingForm() {
            const form = document.getElementById('bookingForm');
            const roomCards = document.querySelectorAll('.room-card');
            const checkinInput = document.getElementById('checkin');
            const checkoutInput = document.getElementById('checkout');
            const guestsInput = document.getElementById('guests');
            const amenityCheckboxes = document.querySelectorAll('input[name="amenities[]"]');
            const serviceCheckboxes = document.querySelectorAll('input[name="services[]"]');

            // Room selection
            roomCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Remove previous selection
                    roomCards.forEach(c => c.classList.remove('selected'));
                    // Select current card
                    this.classList.add('selected');
                    // Check radio button
                    this.querySelector('input[type="radio"]').checked = true;
                    // Update summary
                    updateBookingSummary();
                });
            });

            // Date validation
            checkinInput.addEventListener('change', function() {
                const checkinDate = new Date(this.value);
                const checkoutDate = new Date(checkoutInput.value);
                
                // Set minimum checkout date
                const minCheckout = new Date(checkinDate);
                minCheckout.setDate(minCheckout.getDate() + 1);
                checkoutInput.min = minCheckout.toISOString().split('T')[0];
                
                // Reset checkout if it's before new checkin
                if (checkoutDate <= checkinDate) {
                    checkoutInput.value = '';
                }
                
                updateBookingSummary();
            });

            checkoutInput.addEventListener('change', updateBookingSummary);
            guestsInput.addEventListener('change', updateBookingSummary);

            // Make amenity cards clickable
            const amenityCards = document.querySelectorAll('.amenity-card');
            amenityCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    // Don't trigger if clicking directly on checkbox
                    if (e.target.closest('.amenity-checkbox')) return;
                    
                    const checkbox = card.querySelector('input[type="checkbox"]');
                    const isCurrentlyChecked = checkbox.checked;
                    
                    // Toggle checkbox
                    checkbox.checked = !isCurrentlyChecked;
                    
                    // Update card appearance
                    if (checkbox.checked) {
                        card.classList.add('selected');
                    } else {
                        card.classList.remove('selected');
                    }
                    
                    // Update cost calculation
                    updateBookingSummary();
                });
            });
            
            // Make service cards clickable
            const serviceCards = document.querySelectorAll('.service-card');
            serviceCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    // Don't trigger if clicking directly on checkbox
                    if (e.target.closest('.service-checkbox')) return;
                    
                    const checkbox = card.querySelector('input[type="checkbox"]');
                    const isCurrentlyChecked = checkbox.checked;
                    
                    // Toggle checkbox
                    checkbox.checked = !isCurrentlyChecked;
                    
                    // Update card appearance
                    if (checkbox.checked) {
                        card.classList.add('selected');
                    } else {
                        card.classList.remove('selected');
                    }
                    
                    // Update cost calculation
                    updateBookingSummary();
                });
            });

            // Amenity and service selection (for direct checkbox clicks)
            amenityCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const card = this.closest('.amenity-card');
                    if (this.checked) {
                        card.classList.add('selected');
                    } else {
                        card.classList.remove('selected');
                    }
                    updateBookingSummary();
                });
            });

            serviceCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const card = this.closest('.service-card');
                    if (this.checked) {
                        card.classList.add('selected');
                    } else {
                        card.classList.remove('selected');
                    }
                    updateBookingSummary();
                });
            });

            function updateBookingSummary() {
                const selectedRoom = document.querySelector('.room-card.selected');
                const checkin = checkinInput.value;
                const checkout = checkoutInput.value;
                const guests = guestsInput.value;

                // Calculate nights
                let nights = 0;
                if (checkin && checkout) {
                    const checkinDate = new Date(checkin);
                    const checkoutDate = new Date(checkout);
                    nights = Math.ceil((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));
                }

                // Update night count displays
                document.getElementById('nightCount').textContent = nights;
                document.getElementById('summaryDuration').textContent = `${nights} nights`;

                // Calculate room cost
                let roomRate = 0;
                let roomName = 'Not selected';
                if (selectedRoom) {
                    roomRate = parseFloat(selectedRoom.dataset.roomRate);
                    roomName = selectedRoom.querySelector('h4').textContent;
                }
                const roomCost = roomRate * nights;

                // Calculate amenities cost
                let amenitiesCost = 0;
                amenityCheckboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        amenitiesCost += parseFloat(checkbox.dataset.price);
                    }
                });

                // Calculate services cost
                let servicesCost = 0;
                serviceCheckboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        servicesCost += parseFloat(checkbox.dataset.price);
                    }
                });

                // Calculate total
                const totalCost = roomCost + amenitiesCost + servicesCost;

                // Update summary display
                document.getElementById('selectedRoom').textContent = roomName;
                document.getElementById('roomCost').textContent = `₱${roomCost.toFixed(2)}`;
                document.getElementById('amenitiesCost').textContent = `₱${amenitiesCost.toFixed(2)}`;
                document.getElementById('servicesCost').textContent = `₱${servicesCost.toFixed(2)}`;
                document.getElementById('totalCost').textContent = `₱${totalCost.toFixed(2)}`;

                // Enable/disable book button
                const bookBtn = document.getElementById('bookNowBtn');
                const canBook = selectedRoom && checkin && checkout && guests && nights > 0;
                bookBtn.disabled = !canBook;

                // Check room capacity
                if (selectedRoom && guests) {
                    const roomCapacity = parseInt(selectedRoom.dataset.roomCapacity);
                    const guestCount = parseInt(guests);
                    
                    if (guestCount > roomCapacity) {
                        alert(`Selected room can accommodate up to ${roomCapacity} guests only.`);
                        guestsInput.value = '';
                        bookBtn.disabled = true;
                    }
                }
            }

            // Form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(form);
                const submitBtn = document.getElementById('bookNowBtn');
                
                // Disable button and show loading
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                
                // Submit form
                fetch('process_booking.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Redirect to payment or confirmation page
                        window.location.href = `booking_confirmation.php?booking_id=${data.booking_id}`;
                    } else {
                        alert(data.message || 'An error occurred. Please try again.');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-credit-card"></i> Book Now';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-credit-card"></i> Book Now';
                });
            });
        }
    </script>
</body>
</html>
