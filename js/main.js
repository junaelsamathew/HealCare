/**
 * HealCare Main JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    initNavigation();
    initScrollEffects();
    initAuthModal();
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
