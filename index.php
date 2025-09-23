<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paradise Resort - Your Perfect Getaway</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <h2><i class="fas fa-palm-tree"></i> Paradise Resort</h2>
            </div>
            <div class="nav-menu">
                <a href="#home" class="nav-link">Home</a>
                <a href="#rooms" class="nav-link">Rooms</a>
                <a href="#amenities" class="nav-link">Amenities</a>
                <a href="#services" class="nav-link">Services</a>
                <a href="#contact" class="nav-link">Contact</a>
                <div class="nav-auth" id="navAuth">
                    <!-- This will be populated by JavaScript based on login status -->
                    <div class="user-dropdown">
                        <button class="dropdown-toggle" id="userDropdownToggle">
                            <i class="fas fa-user-circle"></i>
                            <span>Account</span>
                            <i class="fas fa-chevron-down dropdown-arrow"></i>
                        </button>
                        <div class="dropdown-menu" id="userDropdownMenu">
                            <a href="login.php" class="dropdown-item">
                                <i class="fas fa-sign-in-alt"></i>
                                <span>Login</span>
                            </a>
                            <a href="register.php" class="dropdown-item">
                                <i class="fas fa-user-plus"></i>
                                <span>Sign Up</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-content">
            <h1>Welcome to Paradise Resort</h1>
            <p>Experience luxury and comfort in our beautiful accommodation system</p>
            <div class="hero-buttons">
                <button onclick="checkLoginAndRedirect('#rooms')" class="btn btn-primary">Book Now</button>
                <a href="#about" class="btn btn-secondary">Learn More</a>
            </div>
        </div>
        <div class="hero-video">
            <div class="video-overlay"></div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>About Paradise Resort</h2>
                    <p>Nestled in a tropical paradise, our resort offers the perfect blend of luxury and nature. With world-class amenities, exceptional service, and breathtaking views, we create unforgettable experiences for our guests.</p>
                    <div class="about-stats">
                        <div class="stat">
                            <h3>500+</h3>
                            <p>Happy Guests</p>
                        </div>
                        <div class="stat">
                            <h3>3</h3>
                            <p>Room Types</p>
                        </div>
                        <div class="stat">
                            <h3>24/7</h3>
                            <p>Service</p>
                        </div>
                    </div>
                </div>
                <div class="about-image">
                    <img src="assets/images/resort-exterior.jpg" alt="Resort Exterior">
                </div>
            </div>
        </div>
    </section>

    <!-- Rooms Section -->
    <section id="rooms" class="rooms">
        <div class="container">
            <div class="section-header">
                <h2>Our Rooms</h2>
                <p>Choose from our carefully designed accommodations</p>
            </div>
            <div class="rooms-grid">
                <div class="room-card">
                    <div class="room-image">
                        <img src="assets/images/deluxe-room.jpg" alt="Deluxe Room">
                        <div class="room-price">₱2,500/night</div>
                    </div>
                    <div class="room-content">
                        <h3>Deluxe Room</h3>
                        <p>Spacious and elegantly designed room with premium amenities</p>
                        <div class="room-features">
                            <span><i class="fas fa-users"></i> Up to 20 guests</span>
                            <span><i class="fas fa-wifi"></i> Free Wi-Fi</span>
                            <span><i class="fas fa-tv"></i> Smart TV</span>
                        </div>
                        <a href="#" onclick="requireLogin('deluxe')" class="btn btn-primary booking-btn" data-room="deluxe">Book Now</a>
                    </div>
                </div>
                
                <div class="room-card">
                    <div class="room-image">
                        <img src="assets/images/pool-room.jpg" alt="Pool Room">
                        <div class="room-price">₱1,500/night</div>
                    </div>
                    <div class="room-content">
                        <h3>Pool Room</h3>
                        <p>Direct pool access with stunning water views</p>
                        <div class="room-features">
                            <span><i class="fas fa-users"></i> Up to 20 guests</span>
                            <span><i class="fas fa-swimming-pool"></i> Pool Access</span>
                            <span><i class="fas fa-cocktail"></i> Mini Bar</span>
                        </div>
                        <a href="#" onclick="requireLogin('pool')" class="btn btn-primary booking-btn" data-room="pool">Book Now</a>
                    </div>
                </div>
                
                <div class="room-card">
                    <div class="room-image">
                        <img src="assets/images/family-room.jpg" alt="Family Room">
                        <div class="room-price">₱1,500/night</div>
                    </div>
                    <div class="room-content">
                        <h3>Family Room</h3>
                        <p>Perfect for families with kids, featuring extra space and amenities</p>
                        <div class="room-features">
                            <span><i class="fas fa-users"></i> Up to 10 guests</span>
                            <span><i class="fas fa-baby"></i> Kid-Friendly</span>
                            <span><i class="fas fa-gamepad"></i> Game Area</span>
                        </div>
                        <a href="#" onclick="requireLogin('family')" class="btn btn-primary booking-btn" data-room="family">Book Now</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Amenities Section -->
    <section id="amenities" class="amenities">
        <div class="container">
            <div class="section-header">
                <h2>Resort Amenities</h2>
                <p>Enjoy our premium facilities and services</p>
            </div>
            <div class="amenities-grid">
                <div class="amenity-card">
                    <div class="amenity-icon">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <h3>High-Speed Wi-Fi</h3>
                    <p>Stay connected with complimentary high-speed internet throughout the resort</p>
                    <div class="amenity-price">₱500/stay</div>
                </div>
                
                <div class="amenity-card">
                    <div class="amenity-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3>Gourmet Breakfast</h3>
                    <p>Start your day with our delicious breakfast featuring local and international cuisine</p>
                    <div class="amenity-price">₱1,000/person</div>
                </div>
                
                <div class="amenity-card">
                    <div class="amenity-icon">
                        <i class="fas fa-spa"></i>
                    </div>
                    <h3>Luxury Spa</h3>
                    <p>Relax and rejuvenate with our full-service spa treatments</p>
                    <div class="amenity-price">₱800/session</div>
                </div>
                
                <div class="amenity-card">
                    <div class="amenity-icon">
                        <i class="fas fa-motorcycle"></i>
                    </div>
                    <h3>ATV Adventure</h3>
                    <p>Explore the surrounding areas with our guided ATV tours</p>
                    <div class="amenity-price">₱1,500/tour</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="services">
        <div class="container">
            <div class="section-header">
                <h2>Our Services</h2>
                <p>We go above and beyond to make your stay memorable</p>
            </div>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <h3>Airport Pickup</h3>
                    <p>Convenient transportation service from your location to the resort</p>
                    <div class="service-price">₱1,000/trip</div>
                    <a href="#" onclick="requireLogin('pickup')" class="btn btn-outline service-btn" data-service="pickup">Book Service</a>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-concierge-bell"></i>
                    </div>
                    <h3>24/7 Concierge</h3>
                    <p>Our dedicated staff is available round the clock to assist you</p>
                    <div class="service-price">Complimentary</div>
                    <a href="#" onclick="requireLogin('concierge')" class="btn btn-outline service-btn" data-service="concierge">Learn More</a>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3>Room Service</h3>
                    <p>Enjoy delicious meals in the comfort of your room</p>
                    <div class="service-price">Menu Prices</div>
                    <a href="#" onclick="requireLogin('room-service')" class="btn btn-outline service-btn" data-service="room-service">View Menu</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section class="gallery">
        <div class="container">
            <div class="section-header">
                <h2>Gallery</h2>
                <p>Take a glimpse of paradise</p>
            </div>
            <div class="gallery-grid">
                <div class="gallery-item">
                    <img src="assets/images/deluxe-room.jpg" alt="Deluxe Room">
                </div>
                <div class="gallery-item">
                    <img src="assets/images/pool-room.jpg" alt="Pool Room">
                </div>
                <div class="gallery-item">
                    <img src="assets/images/family-room.jpg" alt="Family Room">
                </div>
                <div class="gallery-item">
                    <img src="assets/images/resort-exterior.jpg" alt="Resort Exterior">
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="container">
            <div class="contact-content">
                <div class="contact-info">
                    <h2>Get in Touch</h2>
                    <p>Ready to book your perfect getaway? Contact us today!</p>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h4>Location</h4>
                            <p>Paradise Island, Tropical Haven</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h4>Phone</h4>
                            <p>+63 915 104 6166</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h4>Email</h4>
                            <p>info@paradiseresort.com</p>
                        </div>
                    </div>
                </div>
                <div class="contact-form">
                    <h3>Send us a Message</h3>
                    <form>
                        <div class="form-group">
                            <input type="text" placeholder="Your Name" required>
                        </div>
                        <div class="form-group">
                            <input type="email" placeholder="Your Email" required>
                        </div>
                        <div class="form-group">
                            <input type="tel" placeholder="Your Phone">
                        </div>
                        <div class="form-group">
                            <textarea rows="5" placeholder="Your Message" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-palm-tree"></i> Paradise Resort</h3>
                    <p>Your perfect getaway destination where luxury meets nature. Experience unforgettable moments at Paradise Resort.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="#home">Home</a></li>
                        <li><a href="#rooms">Rooms</a></li>
                        <li><a href="#amenities">Amenities</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Services</h4>
                    <ul>
                        <li><a href="#">Airport Pickup</a></li>
                        <li><a href="#">Room Service</a></li>
                        <li><a href="#">Spa & Wellness</a></li>
                        <li><a href="#">Concierge</a></li>
                        <li><a href="#">Activities</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <p><i class="fas fa-map-marker-alt"></i> Paradise Island, Tropical Haven</p>
                    <p><i class="fas fa-phone"></i> +63 915 104 6166</p>
                    <p><i class="fas fa-envelope"></i> info@paradiseresort.com</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Paradise Resort. All rights reserved. | Powered by Guest Accommodation System</p>
            </div>
        </div>
    </footer>

    <!-- Login Required Modal -->
    <div id="loginModal" class="login-modal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Login Required</h3>
                <button class="modal-close" onclick="closeLoginModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h4>Access Restricted</h4>
                <p>To book rooms and access our services, please log in to your account or create a new one.</p>
                <div class="modal-benefits">
                    <div class="benefit">
                        <i class="fas fa-check"></i>
                        <span>Easy booking management</span>
                    </div>
                    <div class="benefit">
                        <i class="fas fa-check"></i>
                        <span>Exclusive member discounts</span>
                    </div>
                    <div class="benefit">
                        <i class="fas fa-check"></i>
                        <span>Booking history & preferences</span>
                    </div>
                </div>
                <div class="modal-actions">
                    <a href="login.php" class="modal-btn primary">Login</a>
                    <a href="register.php" class="modal-btn secondary">Create Account</a>
                </div>
                <p class="modal-footer-text">
                    Continue browsing as a guest or <a href="#contact">contact us</a> directly for assistance.
                </p>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js?v=<?php echo time(); ?>"></script>
</body>
</html>
