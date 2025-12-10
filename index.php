<?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <header class="hero landing-hero" id="home">

        <div class="container hero-content">
            <div class="hero-text">
                <h1 class="fade-in-up">Healthcare Reimagined <br> <span class="highlight">For The Future</span></h1>
                <p class="fade-in-up delay-1">Experience world-class medical services with state-of-the-art technology
                    and compassionate care.</p>
                <div class="hero-btns fade-in-up delay-2">
                    <button class="btn btn-primary btn-lg">Get Started</button>
                    <button class="btn btn-outline btn-lg">Learn More</button>
                </div>

                <div class="stats-row fade-in-up delay-3">
                    <div class="stat-item">
                        <h3>500+</h3>
                        <p>Doctors</p>
                    </div>
                    <div class="stat-item">
                        <h3>24/7</h3>
                        <p>Emergency</p>
                    </div>
                    <div class="stat-item">
                        <h3>10k+</h3>
                        <p>Patients Healed</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-bg-glow"></div>
    </header>

    <!-- About Us Section -->
    <section id="about" class="section padding-lg bg-darker">
        <div class="container">
            <div class="section-header text-center reveal-on-scroll">
                <span class="badge">Who We Are</span>
                <h2>Redefining <span class="highlight">Healthcare Excellence</span></h2>
                <p>HealCare is a pioneer in modern medicine, combining compassion with advanced technology.</p>
            </div>
            <div class="about-grid">
                <div class="about-content reveal-on-scroll">
                    <h3>Our Mission</h3>
                    <p>To provide accessible, high-quality healthcare to everyone. We believe in a patient-first
                        approach, ensuring that every individual receives personalized care.</p>
                    <ul class="check-list">
                        <li>✅ Top-tier Medical Professionals</li>
                        <li>✅ State-of-the-art Technology</li>
                        <li>✅ 24/7 Care & Support</li>
                    </ul>
                </div>
                <div class="about-stats reveal-on-scroll">
                    <div class="stat-card glass-panel">
                        <h2>25+</h2>
                        <p>Years of Service</p>
                    </div>
                    <div class="stat-card glass-panel">
                        <h2>50+</h2>
                        <p>Awards Won</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Departments / Services Section -->
    <section id="departments" class="section padding-lg">
         <!-- This was conceptually linked to Services in nav, so reusing Services layout or defining new if distinct. 
              The nav says 'Department' and 'Services'. Let's assume they map to this section for now or split them.
              Code reused from HTML 'services' id. 
         -->
        <div class="container">
            <div class="section-header text-center fade-in-up">
                <span class="badge">Our Expertise</span>
                <h2>Comprehensive <span class="highlight">Medical Services</span></h2>
                <p>We provide a wide range of top-tier medical solutions tailored to your needs.</p>
            </div>

            <div class="services-grid" id="services">
                <!-- Service 1 -->
                <div class="service-card glass-panel reveal-on-scroll">
                    <div class="service-icon">🫀</div>
                    <h3>Cardiology</h3>
                    <p>Advanced heart care with cutting-edge diagnostics and expert surgeons.</p>
                    <a href="#" class="btn-text">Learn more &rarr;</a>
                </div>
                <!-- Service 2 -->
                <div class="service-card glass-panel reveal-on-scroll">
                    <div class="service-icon">🧠</div>
                    <h3>Neurology</h3>
                    <p>Comprehensive care for neurological disorders with specialized rehabilitation.</p>
                    <a href="#" class="btn-text">Learn more &rarr;</a>
                </div>
                <!-- Service 3 -->
                <div class="service-card glass-panel reveal-on-scroll">
                    <div class="service-icon">👶</div>
                    <h3>Pediatrics</h3>
                    <p>Compassionate care for infants, children, and adolescents.</p>
                    <a href="#" class="btn-text">Learn more &rarr;</a>
                </div>
                <!-- Service 4 -->
                <div class="service-card glass-panel reveal-on-scroll">
                    <div class="service-icon">🦴</div>
                    <h3>Orthopedics</h3>
                    <p>Joint replacement and sports medicine from world-renowned specialists.</p>
                    <a href="#" class="btn-text">Learn more &rarr;</a>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="insurance" class="cta-section">
        <div class="container cta-content glass-effect">
            <div class="cta-text">
                <h2>Need Emergency Care?</h2>
                <p>Our emergency department is open 24/7 with rapid response teams.</p>
            </div>
            <div class="cta-btn">
                <button class="btn btn-primary btn-lg">Call 911-HEAL</button>
            </div>
        </div>
    </section>

    <!-- Doctors Section -->
    <section id="doctors" class="section padding-lg bg-darker">
        <div class="container">
            <div class="section-header text-center reveal-on-scroll">
                <span class="badge">Our Team</span>
                <h2>Meet Our <span class="highlight">Specialists</span></h2>
                <p>Led by industry pioneers dedicated to your well-being.</p>
            </div>

            <div class="doctors-slider reveal-on-scroll">
                <!-- Doctor Card 1 -->
                <div class="doctor-card">
                    <div class="doctor-img-placeholder gradient-1"></div>
                    <div class="doctor-info">
                        <h4>Dr. Sarah Jenny</h4>
                        <span class="specialty">Chief Cardiologist</span>
                        <p>15+ years experience in interventional cardiology.</p>
                    </div>
                </div>
                <!-- Doctor Card 2 -->
                <div class="doctor-card">
                    <div class="doctor-img-placeholder gradient-2"></div>
                    <div class="doctor-info">
                        <h4>Dr. Mark Stevens</h4>
                        <span class="specialty">Senior Neurologist</span>
                        <p>Specializing in complex brain surgeries and research.</p>
                    </div>
                </div>
                <!-- Doctor Card 3 -->
                <div class="doctor-card">
                    <div class="doctor-img-placeholder gradient-3"></div>
                    <div class="doctor-info">
                        <h4>Dr. Emily Chen</h4>
                        <span class="specialty">Head of Pediatrics</span>
                        <p>Dedicated to providing the best care for your little ones.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Book Appointment Section -->
    <section id="book-appointment" class="section padding-lg bg-darker">
        <div class="container">
            <div class="section-header text-center reveal-on-scroll">
                <span class="badge">Booking</span>
                <h2>Manage Your <span class="highlight">Appointment</span></h2>
                <p>Easy scheduling and management with our top specialists.</p>
            </div>

            <div class="appointment-tabs glass-panel reveal-on-scroll">
                <button class="appt-tab active" onclick="switchApptTab('book')">Book New</button>
                <button class="appt-tab" onclick="switchApptTab('cancel')">Cancel / Check</button>
            </div>

            <!-- Booking Form -->
            <form id="bookForm" class="appointment-form glass-panel reveal-on-scroll">
                <div class="form-row">
                    <input type="text" placeholder="Full Name" required>
                    <input type="email" placeholder="Email Address" required>
                </div>
                <div class="form-row">
                    <select required>
                        <option value="" disabled selected>Select Department</option>
                        <option value="cardiology">Cardiology</option>
                        <option value="neurology">Neurology</option>
                        <option value="pediatrics">Pediatrics</option>
                        <option value="orthopedics">Orthopedics</option>
                    </select>
                    <input type="date" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Confirm Booking</button>
            </form>

            <!-- Cancel Form (Hidden by default) -->
            <form id="cancelForm" class="appointment-form glass-panel reveal-on-scroll" style="display: none;">
                <div class="form-row">
                    <input type="text" placeholder="Appointment ID" required>
                </div>
                <button type="submit" class="btn btn-outline btn-block"
                    style="border-color: #ef4444; color: #ef4444;">Cancel Appointment</button>
            </form>
        </div>
    </section>

    <!-- Find a Doctor Section -->
    <section id="find-doctor" class="section padding-lg">
        <div class="container">
            <div class="section-header text-center reveal-on-scroll">
                <span class="badge">Search</span>
                <h2>Find a <span class="highlight">Doctor</span></h2>
                <p>Search by name, specialty, or department.</p>
            </div>
            <div class="search-bar glass-panel reveal-on-scroll">
                <input type="text" placeholder="Search doctor's name or specialty..." class="search-input">
                <button class="btn btn-primary">Search</button>
            </div>
        </div>
    </section>

    <!-- Latest News & Blogs -->
    <section id="news" class="section padding-lg">
        <div class="container">
            <div class="section-header text-center reveal-on-scroll">
                <span class="badge">Updates</span>
                <h2>Latest <span class="highlight">News & Articles</span></h2>
                <p>Stay informed with the latest medical breakthroughs and health tips.</p>
            </div>
            <div class="news-grid">
                <article class="news-card glass-panel reveal-on-scroll">
                    <div class="news-img img-1"></div>
                    <div class="news-content">
                        <span class="date">Oct 24, 2025</span>
                        <h4>The Future of Robotic Surgery</h4>
                        <p>How AI and robotics are revolutionizing complex procedures.</p>
                        <a href="#" class="read-more">Read More &rarr;</a>
                    </div>
                </article>
                <article class="news-card glass-panel reveal-on-scroll">
                    <div class="news-img img-2"></div>
                    <div class="news-content">
                        <span class="date">Oct 18, 2025</span>
                        <h4>Heart Health Tips for Seniors</h4>
                        <p>Essential advice for maintaining cardiovascular health.</p>
                        <a href="#" class="read-more">Read More &rarr;</a>
                    </div>
                </article>
                <article class="news-card glass-panel reveal-on-scroll">
                    <div class="news-img img-3"></div>
                    <div class="news-content">
                        <span class="date">Oct 10, 2025</span>
                        <h4>Mental Health Awareness</h4>
                        <p>Understanding the importance of mental well-being used today.</p>
                        <a href="#" class="read-more">Read More &rarr;</a>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- Patient Stories -->
    <section id="stories" class="section padding-lg bg-darker">
        <div class="container">
            <div class="section-header text-center reveal-on-scroll">
                <span class="badge">Testimonials</span>
                <h2>Patient <span class="highlight">Stories</span></h2>
                <p>Real stories of hope and healing from our patients.</p>
            </div>
            <div class="stories-grid">
                <div class="story-card glass-panel reveal-on-scroll">
                    <p class="quote">"The care I received at HealCare was nothing short of miraculous. The cardiology
                        team saved my life."</p>
                    <div class="author">
                        <div class="avatar">👨</div>
                        <div>
                            <h5>John Doe</h5>
                            <span>Recovered Patient</span>
                        </div>
                    </div>
                </div>
                <div class="story-card glass-panel reveal-on-scroll">
                    <p class="quote">"Professional, kind, and state-of-the-art facilities. I felt safe and cared for
                        every step of the way."</p>
                    <div class="author">
                        <div class="avatar">👩</div>
                        <div>
                            <h5>Jane Smith</h5>
                            <span>Maternity Patient</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="section padding-lg bg-darker">
        <div class="container">
            <div class="section-header text-center reveal-on-scroll">
                <span class="badge">Get in Touch</span>
                <h2>Contact <span class="highlight">Us</span></h2>
                <p>We are here to assist you 24/7.</p>
            </div>
            <div class="contact-grid">
                <div class="contact-card glass-panel reveal-on-scroll">
                    <div class="icon-box">🚨</div>
                    <h3>Emergency</h3>
                    <p class="highlight-text">911 / 112</p>
                </div>
                <div class="contact-card glass-panel reveal-on-scroll">
                    <div class="icon-box">🚑</div>
                    <h3>Ambulance</h3>
                    <p class="highlight-text">+1 (800) AMB-HELP</p>
                </div>
                <div class="contact-card glass-panel reveal-on-scroll">
                    <div class="icon-box">📍</div>
                    <h3>Address</h3>
                    <p>123 Healthcare Ave, Medical City, NY</p>
                </div>
                <div class="contact-card glass-panel reveal-on-scroll">
                    <div class="icon-box">📞</div>
                    <h3>General Support</h3>
                    <p>+1 (555) 123-4567</p>
                </div>
                <div class="contact-card glass-panel reveal-on-scroll">
                    <div class="icon-box">📧</div>
                    <h3>Email</h3>
                    <p>contact@healcare.com</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Social rail + floating actions -->
    <div class="social-rail">
        <a href="#" aria-label="Facebook">f</a>
        <a href="#" aria-label="Twitter">t</a>
        <a href="#" aria-label="YouTube">▶</a>
        <a href="#" aria-label="Instagram">●</a>
    </div>

    <div class="floating-actions">
        <a class="fab whatsapp" href="https://wa.me/918281262626" target="_blank" aria-label="WhatsApp">☎</a>
        <a class="fab call" href="tel:+918086611101" aria-label="Call">📞</a>
    </div>

<?php include 'includes/footer.php'; ?>
