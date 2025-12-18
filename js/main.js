/**
 * HealCare Main JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    initNavigation();
    initScrollEffects();

    initAuthModal();
    initChatWidget();
    initCarousel();
});

function initNavigation() {


    const navbar = document.querySelector('.navbar');

    // Sticky Navbar Glass Effect Enhancement on Scroll
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.style.background = 'rgba(15, 23, 42, 0.9)';
            navbar.style.boxShadow = '0 4px 20px rgba(0,0,0,0.2)';
        } else {
            navbar.style.background = 'rgba(255, 255, 255, 0.05)'; // Original semi-transparent
            navbar.style.boxShadow = 'none';
        }
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId && targetId !== '#') {
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            }
        });
    });
}

function initScrollEffects() {
    // Reveal elements on scroll (Simple Intersection Observer)
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1 });

    // Assuming we will add 'reveal-on-scroll' classes to future sections
    document.querySelectorAll('.reveal-on-scroll').forEach(el => observer.observe(el));
}

function initAuthModal() {
    const loginBtn = document.getElementById('loginBtn');
    const modal = document.getElementById('authModal');
    const closeBtn = document.querySelector('.close-modal');
    const tabs = document.querySelectorAll('.tab-btn');
    const forms = document.querySelectorAll('.auth-form');

    if (!loginBtn || !modal) return;

    // Open Modal
    loginBtn.addEventListener('click', () => {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    });

    // Close Modal
    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    // Close on click outside
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    // Tab Switching
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove active class from all
            tabs.forEach(t => t.classList.remove('active'));
            forms.forEach(f => f.classList.remove('active'));

            // Add active to clicked
            tab.classList.add('active');

            // Show corresponding form
            const target = tab.getAttribute('data-tab');
            if (target === 'login') {
                document.getElementById('loginForm').classList.add('active');
            } else {
                document.getElementById('signupForm').classList.add('active');
            }
        });
    });


    // Handle Role Selection
    const roleSelect = document.getElementById('userRole');
    const doctorFields = document.getElementById('modalDoctorFields');

    if (roleSelect && doctorFields) {
        roleSelect.addEventListener('change', (e) => {
            const role = e.target.value;

            // Update Hidden Inputs
            document.getElementById('loginRole').value = role;
            document.getElementById('signupRole').value = role;

            if (role === 'doctor') {
                doctorFields.style.display = 'block';
            } else {
                doctorFields.style.display = 'none';
            }
        });
    }
}

function switchApptTab(tab) {
    const bookForm = document.getElementById('bookForm');
    const cancelForm = document.getElementById('cancelForm');
    const tabs = document.querySelectorAll('.appt-tab');

    // Toggle forms
    if (tab === 'book') {
        bookForm.style.display = 'block';
        cancelForm.style.display = 'none';
        tabs[0].classList.add('active');
        tabs[1].classList.remove('active');
    } else {
        bookForm.style.display = 'none';
        cancelForm.style.display = 'block';
        tabs[0].classList.remove('active');
        tabs[1].classList.add('active');
    }
}

/* -------------------------------------------------------------------------- */
/*                                CHAT WIDGET                                 */
/* -------------------------------------------------------------------------- */
function initChatWidget() {
    const chatToggle = document.getElementById('chatToggle');
    const chatWidget = document.getElementById('chatWidget');
    const closeChat = document.getElementById('closeChat');
    const chatInput = document.getElementById('chatInput');
    const sendMessage = document.getElementById('sendMessage');
    const chatBody = document.getElementById('chatBody');

    if (!chatToggle || !chatWidget) return;

    // Toggle Chat Visibility
    function toggleChat() {
        const isHidden = chatWidget.classList.contains('hidden') || chatWidget.style.display === 'none';

        if (isHidden) {
            chatWidget.style.display = 'flex';
            // Small delay to allow display flex to apply before removing hidden class for animation
            setTimeout(() => {
                chatWidget.classList.remove('hidden');
                chatInput.focus();
            }, 10);
        } else {
            chatWidget.classList.add('hidden');
            setTimeout(() => {
                chatWidget.style.display = 'none';
            }, 300); // Wait for transition
        }
    }

    chatToggle.addEventListener('click', toggleChat);
    closeChat.addEventListener('click', toggleChat);

    // Send Message Logic
    function handleSendMessage() {
        const text = chatInput.value.trim();
        if (!text) return;

        // Add User Message
        appendMessage(text, 'user-message');
        chatInput.value = '';

        // Simulate Bot Typing/Reply
        setTimeout(() => {
            const reply = getBotReply(text);
            appendMessage(reply, 'bot-message');
        }, 1000);
    }

    sendMessage.addEventListener('click', handleSendMessage);
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') handleSendMessage();
    });

    function appendMessage(text, className) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `message ${className}`;
        msgDiv.textContent = text;
        chatBody.appendChild(msgDiv);
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    function getBotReply(input) {
        input = input.toLowerCase();
        if (input.includes('hello') || input.includes('hi')) return "Hello! How can I help you today?";
        if (input.includes('appointment') || input.includes('book')) return "You can book an appointment by scrolling to the 'Book Appointment' section or clicking the button in the navigation.";
        if (input.includes('doctor')) return "We have many specialists. You can find them in the 'Find a Doctor' section.";
        if (input.includes('time') || input.includes('hour')) return "Our emergency department is open 24/7. Regular OPD hours are 9 AM to 6 PM.";
        if (input.includes('location') || input.includes('address')) return "We are located at 123 Healthcare Ave, Medical City, NY.";
        return "I'm not sure about that. Please contact our support team at +1 (555) 123-4567 for more details.";
    }
}

/* -------------------------------------------------------------------------- */
/*                                CAROUSEL                                    */
/* -------------------------------------------------------------------------- */
function initCarousel() {
    const track = document.querySelector('.carousel-track');
    // Using a more robust selection in case of multiple carousels later, but fine for now
    const slides = Array.from(track ? track.children : []);
    const nextBtn = document.querySelector('.next-btn');
    const prevBtn = document.querySelector('.prev-btn');
    const indicators = document.querySelectorAll('.indicator');

    if (!track || slides.length === 0) return;

    let currentIndex = 0;
    const slideIntervalPrice = 5000;
    let autoSlideInterval;

    // Arrange slides next to one another? No, we transform the track.
    // CSS .carousel-slide has min-width: 100%. Flex track moves.

    function updateSlidePosition() {
        track.style.transform = 'translateX(-' + (currentIndex * 100) + '%)';

        // Update Indicators
        indicators.forEach((ind, index) => {
            if (index === currentIndex) {
                ind.classList.add('active');
            } else {
                ind.classList.remove('active');
            }
        });

        // Trigger animations for content
        slides.forEach((slide, index) => {
            if (index === currentIndex) {
                slide.classList.add('active');
            } else {
                slide.classList.remove('active');
            }
        });
    }

    function moveToNextSlide() {
        if (currentIndex === slides.length - 1) {
            currentIndex = 0;
        } else {
            currentIndex++;
        }
        updateSlidePosition();
    }

    function moveToPrevSlide() {
        if (currentIndex === 0) {
            currentIndex = slides.length - 1;
        } else {
            currentIndex--;
        }
        updateSlidePosition();
    }

    // Event Listeners
    if (nextBtn) nextBtn.addEventListener('click', () => {
        moveToNextSlide();
        resetAutoSlide();
    });

    if (prevBtn) prevBtn.addEventListener('click', () => {
        moveToPrevSlide();
        resetAutoSlide();
    });

    indicators.forEach((ind, index) => {
        ind.addEventListener('click', () => {
            currentIndex = index;
            updateSlidePosition();
            resetAutoSlide();
        });
    });

    // Auto Slide
    function startAutoSlide() {
        autoSlideInterval = setInterval(moveToNextSlide, slideIntervalPrice);
    }

    function stopAutoSlide() {
        clearInterval(autoSlideInterval);
    }

    function resetAutoSlide() {
        stopAutoSlide();
        startAutoSlide();
    }

    // Start
    startAutoSlide();

    // Pause on hover
    const container = document.querySelector('.carousel-container');
    container.addEventListener('mouseenter', stopAutoSlide);
    container.addEventListener('mouseleave', startAutoSlide);
}

/* -------------------------------------------------------------------------- */
/*                                GOOGLE SIGN-IN                              */
/* -------------------------------------------------------------------------- */
window.handleCredentialResponse = function (response) {
    console.log("Encoded JWT ID token: " + response.credential);

    // In a real application, you would send this token to your backend
    // fetch('/auth/google', {
    //     method: 'POST',
    //     headers: { 'Content-Type': 'application/json' },
    //     body: JSON.stringify({ token: response.credential })
    // });

    // Mock Login for Demo
    alert("Google Sign-In Successful! Token received (check console).");
    document.querySelector('.close-modal').click();

    // Update UI to show logged in state (Mock)
    const loginLink = document.querySelector('.nav-actions a');
    if (loginLink) {
        loginLink.textContent = "Welcome, User";
        loginLink.href = "#";
        loginLink.classList.remove('btn-secondary');
        loginLink.classList.add('btn-primary');
    }
}
