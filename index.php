<?php 
include 'includes/db_connect.php'; 
include 'includes/header.php'; 
?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-bg">
            <img src="images/bgimg.jpg" alt="HealCare Hospital Building">
            <div class="hero-overlay"></div>
        </div>
        
        <div class="container hero-container">
            <div class="hero-content">
                <span class="welcome-text">WELCOME TO HEALCARE</span>
                <h1>A Great Place to Receive Care</h1>
                <p>"Caring for you, digitally and compassionately — HealCare is health made simple."</p>
                
                <!-- Action Cards -->
                <div class="action-cards">
                    <!-- 1. Find a Doctor -->
                    <a href="find_doctor.php" class="action-card primary" style="text-decoration: none; color: inherit;">
                        <span class="card-text">Find a Doctor</span>
                        <div class="card-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/>
                                <circle cx="17" cy="7" r="4"/>
                                <path d="M21 21v-2a4 4 0 0 0-3-3.87"/>
                            </svg>
                        </div>
                    </a>

                    <!-- 2. Book an Appointment -->
                    <a href="book_appointment.php" class="action-card primary" style="text-decoration: none; color: inherit;">
                        <span class="card-text">Book an Appointment</span>
                        <div class="card-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                                <rect x="7" y="14" width="3" height="3"/>
                                <rect x="14" y="14" width="3" height="3"/>
                            </svg>
                        </div>
                    </a>

                    <!-- 3. Health Checkup -->
                    <a href="health_packages.php" class="action-card primary" style="text-decoration: none; color: inherit;">
                        <span class="card-text">Book a Health Checkup</span>
                        <div class="card-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="2" y="6" width="20" height="12" rx="2"/>
                                <circle cx="8" cy="12" r="2"/>
                                <circle cx="16" cy="12" r="2"/>
                                <line x1="11" y1="12" x2="13" y2="12"/>
                            </svg>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Who We Are Section -->
    <section id="about" class="who-we-are">
        <div class="who-we-are-overlay"></div>
        <div class="container">
            <div class="who-we-are-content">
                <div class="quote-icon">"</div>
                <h2>WHO WE ARE</h2>
                <div class="who-we-are-slider">
                    <div class="who-slide active" data-index="0" style="display: block;">
                        <p>HealCare Hospital is a modern digital healthcare platform committed to delivering compassionate, accessible, and reliable medical services. Built with a patient-first approach, HealCare integrates doctors, staff, laboratories, and administrative services into one secure system, ensuring efficient care and seamless hospital operations for everyone.</p>
                    </div>
                    <div class="who-slide" data-index="1" style="display: none;">
                        <p>Our mission is to provide world-class medical facilities with a human touch, ensuring that every patient receives personalized attention and the best possible outcomes through our integrated healthcare delivery model that focuses on holistic healing.</p>
                    </div>
                    <div class="who-slide" data-index="2" style="display: none;">
                        <p>With a team of highly qualified specialists and state-of-the-art technology, we are redefining healthcare standards to make medical excellence accessible to all segments of society while maintaining the highest levels of safety and quality.</p>
                    </div>
                </div>
                <div class="dots who-dots">
                    <span class="dot active" data-index="0"></span>
                    <span class="dot" data-index="1"></span>
                    <span class="dot" data-index="2"></span>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Specialties Section -->
    <section id="services" class="specialties-section">
        <div class="container">
            <div class="section-header">
                <span class="section-subtitle">ALWAYS CARING</span>
                <h2>Our Specialties</h2>
            </div>
            <div class="specialties-grid">
                <!-- General Medicine / Cardiovascular -->
                <div class="specialty-card">
                    <div class="specialty-icon">
                        <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="#1e90ff" stroke-width="1.5">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                    </div>
                    <span class="specialty-name">General Medicine / Cardiovascular</span>
                </div>
                
                <!-- Gynecology -->
                <div class="specialty-card">
                    <div class="specialty-icon">
                        <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="#1e90ff" stroke-width="1.5">
                            <circle cx="12" cy="8" r="5"/>
                            <path d="M12 13v8M9 18h6"/>
                        </svg>
                    </div>
                    <span class="specialty-name">Gynecology</span>
                </div>
                
                <!-- Orthopedics (Bones) -->
                <div class="specialty-card active">
                    <div class="specialty-icon">
                        <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                        </svg>
                    </div>
                    <span class="specialty-name">Orthopedics (Bones)</span>
                </div>
                
                <!-- ENT -->
                <div class="specialty-card">
                    <div class="specialty-icon">
                        <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="#1e90ff" stroke-width="1.5">
                            <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z"/>
                            <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                            <line x1="12" y1="19" x2="12" y2="23"/>
                            <line x1="8" y1="23" x2="16" y2="23"/>
                        </svg>
                    </div>
                    <span class="specialty-name">ENT</span>
                </div>
                
                <!-- Ophthalmology -->
                <div class="specialty-card">
                    <div class="specialty-icon">
                        <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="#1e90ff" stroke-width="1.5">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </div>
                    <span class="specialty-name">Ophthalmology</span>
                </div>
                
                <!-- Dermatology -->
                <div class="specialty-card">
                    <div class="specialty-icon">
                        <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="#1e90ff" stroke-width="1.5">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            <circle cx="12" cy="12" r="2"/>
                        </svg>
                    </div>
                    <span class="specialty-name">Dermatology</span>
                </div>
            </div>
        </div>
    </section>
    



    <!-- Team of Professionals Section -->
    <section id="doctors" class="doctors-section">
        <div class="container">
            <div class="doctors-layout">
                <!-- Left Side: Content -->
                <div class="doctors-left">
                    <div class="section-header-left">
                        <span class="section-subtitle">TEAM OF PROFESSIONALS</span>
                        <h2 class="doctors-title">Meet your doctor</h2>
                        <p class="doctors-description">
                            HealCare Hospital, a team of around 30 consultant doctors with Experience, Expertise and Academic along with Technology cater to serve the needy with quality care and excellence in medication with an affordable price and uncompromising Nursing Care. 
                            HealCare Hospital is a small hospital which offers comprehensive medical care in specialized departments including General Medicine / Cardiovascular, Gynecology, Orthopedics (Bones), ENT, Ophthalmology, and Dermatology.
                            We have a vast pool of dexterous and experienced team of doctors, who are further supported by a team of highly qualified, experienced & dedicated support staff & cutting edge technology. 
                            More than 50 consultants and 40 employees work together to manage over 15000 patients every year. The hospital has an infrastructure comprising of 100 beds.
                        </p>
                    </div>
                    <div class="team-cta">
                        <a href="find_doctor.php" style="text-decoration: none;">
                            <button class="btn-team-consultants">Team Of Consultants</button>
                        </a>
                    </div>
                </div>

                <!-- Right Side: Doctors Display -->
                <div class="doctors-right">
                    <!-- Featured Doctors Grid/Carousel -->
                    <div class="doctors-display-wrapper">
                        <div class="doctors-cards-slider">
                            <?php
                            $target_doctors = [
                                'mary.mariam@healcare.com' => 'General Medicine',
                                'leena.jose@healcare.com' => 'Gynecology',
                                'jacob.mathew@healcare.com' => 'Orthopedics',
                                'krishnan.manoj@healcare.com' => 'ENT',
                                'june.antony@healcare.com' => 'Ophthalmology',
                                'alan.thomas@healcare.com' => 'Dermatology',
                                'suresh.k@healcare.com' => 'General Medicine',
                                'maria.vineeth@healcare.com' => 'Pediatrics'
                            ];
                            
                            // Fetch Doctor Data
                            $doc_query = "SELECT r.name, r.email, r.profile_photo, d.department 
                                          FROM registrations r 
                                          JOIN users u ON r.registration_id = u.registration_id 
                                          LEFT JOIN doctors d ON u.user_id = d.user_id 
                                          WHERE r.email IN ('" . implode("','", array_keys($target_doctors)) . "') 
                                          ORDER BY FIELD(r.email, '" . implode("','", array_keys($target_doctors)) . "')";
                            $doc_res = $conn->query($doc_query);
                            $docs = [];
                            while($d = $doc_res->fetch_assoc()) $docs[] = $d;

                            // Chunk into pairs for slides
                            $chunks = array_chunk($docs, 2);
                            foreach($chunks as $idx => $pair):
                                $display = ($idx == 0) ? 'grid' : 'none';
                                $active = ($idx == 0) ? 'active' : '';
                            ?>
                            <div class="doctor-slide <?php echo $active; ?>" style="display: <?php echo $display; ?>;">
                                <?php foreach($pair as $doc): 
                                    $img = $doc['profile_photo'] ? $doc['profile_photo'] : 'images/default-doctor.jpg';
                                    $dept = $doc['department'] ? $doc['department'] : $target_doctors[$doc['email']];
                                ?>
                                <div class="doctor-profile-card">
                                    <div class="profile-image-container">
                                        <div class="profile-image-circle">
                                            <img src="<?php echo $img; ?>" alt="<?php echo $doc['name']; ?>" style="width:100%; height:100%; object-fit:cover;">
                                        </div>
                                    </div>
                                    <div class="profile-info">
                                        <h3><?php echo $doc['name']; ?></h3>
                                        <p class="profile-specialty"><?php echo $dept; ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- We Provide Best Care Section -->
    <section class="best-care-section">
        <div class="container text-center">
            <div class="section-header white">
                <h2>We Provide Best Care</h2>
                <p>HealCare Hospital ensures to provide the highest quality of care and a transformative experience for all your healthcare needs. Our multi-speciality hospital equipped with specialised doctors, and world-class technology, bring global standards of medical care to our patients.</p>
            </div>
            
            <div class="care-grid">
                <!-- Diagnostic Center -->
                <div class="care-card card-diag">
                    <div class="care-overlay"></div>
                    <div class="care-content">
                        <h3>Diagnostic Center</h3>
                        <p>Our state-of-the-art diagnostic center offers a comprehensive range of laboratory and imaging services for accurate and rapid diagnosis.</p>
                        <a href="#" class="know-more">KNOW MORE <span>&rarr;</span></a>
                    </div>
                </div>

                <!-- Health Packages -->
                <div class="care-card card-pkg">
                    <div class="care-overlay"></div>
                    <div class="care-content">
                        <h3>Health Packages</h3>
                        <p>Don't ignore the signals from your body before it's too late. Take the step toward preventive health with our comprehensive packages.</p>
                        <a href="#" class="know-more">KNOW MORE <span>&rarr;</span></a>
                    </div>
                </div>

                <!-- Home Care -->
                <div class="care-card card-home">
                    <div class="care-overlay"></div>
                    <div class="care-content">
                        <h3>Home Care</h3>
                        <p>HealCare Hospital aims at bringing personalized and quality healthcare services to the comfort of your home.</p>
                        <a href="#" class="know-more">KNOW MORE <span>&rarr;</span></a>
                    </div>
                </div>

                <!-- Community Clinics -->
                <div class="care-card card-comm">
                    <div class="care-overlay"></div>
                    <div class="care-content">
                        <h3>Community Clinics</h3>
                        <p>We believe in making healthcare accessible. Visit our community clinics for affordable, high-quality primary care near you.</p>
                        <a href="#" class="know-more">KNOW MORE <span>&rarr;</span></a>
                    </div>
                </div>

                <!-- Emergency -->
                <div class="care-card card-emg">
                    <div class="care-overlay"></div>
                    <div class="care-content">
                        <h3>Emergency</h3>
                        <p>Our Emergency Department is geared to meet all medical and surgical emergencies, including pediatric and trauma care 24/7.</p>
                        <a href="#" class="know-more">KNOW MORE <span>&rarr;</span></a>
                    </div>
                </div>

                <!-- 24x7 Pharmacy -->
                <div class="care-card card-phm">
                    <div class="care-overlay"></div>
                    <div class="care-content">
                        <h3>24x7 Pharmacy</h3>
                        <p>Accessible round the clock, our well-stocked pharmacy ensures that the medicines you need are always available.</p>
                        <a href="#" class="know-more">KNOW MORE <span>&rarr;</span></a>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- News Section -->
    <section id="news" class="news-section">
        <div class="container">
            <div class="section-header">
                <span class="section-subtitle">BETTER INFORMATION, BETTER HEALTH</span>
                <h2>News</h2>
            </div>
            <div class="news-grid">
                <div class="news-card">
                    <div class="news-image">
                        <img src="images/news-1.jpg" alt="Health Camp">
                    </div>
                    <div class="news-content">
                        <span class="news-date">Monday 16, December 2024 | <span class="author">By Admin</span></span>
                        <h4>HealCare Launches Free Health Camp for Local Community</h4>
                        <div class="news-stats">
                            <span class="views">👁 124</span>
                            <span class="likes">❤ 86</span>
                        </div>
                    </div>
                </div>
                <div class="news-card">
                    <div class="news-image">
                        <img src="images/news-2.jpg" alt="Cardiology Wing">
                    </div>
                    <div class="news-content">
                        <span class="news-date">Friday 13, December 2024 | <span class="author">By Admin</span></span>
                        <h4>New Cardiology Wing Inaugurated at HealCare Hospital</h4>
                        <div class="news-stats">
                            <span class="views">👁 98</span>
                            <span class="likes">❤ 72</span>
                        </div>
                    </div>
                </div>
                <div class="news-card">
                    <div class="news-image">
                        <img src="images/news-3.jpg" alt="Excellence Award">
                    </div>
                    <div class="news-content">
                        <span class="news-date">Wednesday 11, December 2024 | <span class="author">By Admin</span></span>
                        <h4>HealCare Receives Excellence Award in Patient Care</h4>
                        <div class="news-stats">
                            <span class="views">👁 156</span>
                            <span class="likes">❤ 112</span>
                        </div>
                    </div>
                </div>
                <div class="news-card">
                    <div class="news-image">
                        <img src="images/news-1.jpg" alt="Blood Donation">
                    </div>
                    <div class="news-content">
                        <span class="news-date">Monday 09, December 2024 | <span class="author">By Admin</span></span>
                        <h4>Blood Donation Drive: Over 200 Donors Participate</h4>
                        <div class="news-stats">
                            <span class="views">👁 89</span>
                            <span class="likes">❤ 94</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dots">
                <span class="dot"></span>
                <span class="dot active"></span>
                <span class="dot"></span>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact-section">
        <div class="container">
            <div class="section-header">
                <span class="section-subtitle">GET IN TOUCH</span>
                <h2>Contact</h2>
            </div>
            <div class="contact-grid">
                <div class="contact-card">
                    <div class="contact-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#0a1f44" stroke-width="1.5">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                    </div>
                    <h3>EMERGENCY</h3>
                    <p class="contact-details">
                        +91 7177831464<br>
                       
                    </p>
                </div>
                <div class="contact-card dark">
                    <div class="contact-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </div>
                    <h3>LOCATION</h3>
                    <p class="contact-details">
                        Kanjirapally, Kottayam<br>
                        Kerala, India 686512
                    </p>
                </div>
                <div class="contact-card">
                    <div class="contact-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#0a1f44" stroke-width="1.5">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                    </div>
                    <h3>EMAIL</h3>
                    <p class="contact-details">
                        info@healcare.com<br>
                        support@healcare.com
                    </p>
                </div>
                <div class="contact-card">
                    <div class="contact-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#0a1f44" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                    </div>
                    <h3>WORKING HOURS</h3>
                    <p class="contact-details">
                        Mon-Sat 09:00-20:00<br>
                        Sunday Emergency only
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Floating Social Bar -->
    <div class="social-side-bar">
        <a href="#" class="social-side-item fb"><i class="fab fa-facebook-f"></i></a>
        <a href="#" class="social-side-item tw"><i class="fab fa-twitter"></i></a>
        <a href="#" class="social-side-item yt"><i class="fab fa-youtube"></i></a>
        <a href="#" class="social-side-item ig"><i class="fab fa-instagram"></i></a>
    </div>

    <!-- Floating Action Buttons -->
    <div class="floating-actions">
        <a href="https://wa.me/254717783146" class="float-btn whatsapp-btn" target="_blank">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        </a>
        <a href="tel:+254717783146" class="float-btn call-btn">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
        </a>
    </div>

    <script>
        // Who We Are Slider Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const slides = document.querySelectorAll('.who-slide');
            const dots = document.querySelectorAll('.who-dots .dot');
            let currentSlide = 0;
            let slideInterval;

            function showSlide(index) {
                slides.forEach((slide, i) => {
                    slide.classList.remove('active');
                    slide.style.display = 'none';
                    dots[i].classList.remove('active');
                });
                
                slides[index].classList.add('active');
                slides[index].style.display = 'block';
                dots[index].classList.add('active');
                currentSlide = index;
            }

            function nextSlide() {
                let next = (currentSlide + 1) % slides.length;
                showSlide(next);
            }

            function startInterval() {
                clearInterval(slideInterval);
                slideInterval = setInterval(nextSlide, 3000);
            }

            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => {
                    showSlide(index);
                    startInterval();
                });
            });

            // Swipe functionality
            let touchStartX = 0;
            let touchEndX = 0;
            const slider = document.querySelector('.who-we-are-slider');

            slider.addEventListener('touchstart', e => {
                touchStartX = e.changedTouches[0].screenX;
            }, false);

            slider.addEventListener('touchend', e => {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipe();
            }, false);

            function handleSwipe() {
                if (touchEndX < touchStartX - 50) {
                    // Swipe left - next slide
                    nextSlide();
                    startInterval();
                }
                if (touchEndX > touchStartX + 50) {
                    // Swipe right - prev slide
                    let prev = (currentSlide - 1 + slides.length) % slides.length;
                    showSlide(prev);
                    startInterval();
                }
            }

            startInterval();
        });

        // Doctors Slider Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const doctorSlides = document.querySelectorAll('.doctor-slide');
            let currentDoctorSlide = 0;

            if (doctorSlides.length === 0) return;

            function showNextDoctors() {
                // Hide current
                doctorSlides[currentDoctorSlide].classList.remove('active');
                doctorSlides[currentDoctorSlide].style.display = 'none';

                // Move to next
                currentDoctorSlide = (currentDoctorSlide + 1) % doctorSlides.length;

                // Show next
                doctorSlides[currentDoctorSlide].classList.add('active');
                doctorSlides[currentDoctorSlide].style.display = 'grid';
            }

            setInterval(showNextDoctors, 3000);
        });
    </script>
<?php include 'includes/footer.php'; ?>
