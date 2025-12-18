/**
 * Login Page Logic
 */

document.addEventListener('DOMContentLoaded', () => {
    // Tab Switching
    const tabs = document.querySelectorAll('.tab-btn');
    const forms = document.querySelectorAll('.auth-form');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove active classes
            tabs.forEach(t => t.classList.remove('active'));
            forms.forEach(f => f.classList.remove('active'));

            // Add active class to clicked tab
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

    // Handle Doctor Fields in Signup
    const signupRoleSelect = document.getElementById('signupRoleSelect');
    const doctorFields = document.getElementById('doctorFields');

    if (signupRoleSelect && doctorFields) {
        signupRoleSelect.addEventListener('change', function () {
            if (this.value === 'doctor') {
                doctorFields.style.display = 'block';
                // Add required attribute to inputs inside if visible
                doctorFields.querySelectorAll('input, select').forEach(field => {
                    field.setAttribute('required', 'true');
                });
            } else {
                doctorFields.style.display = 'none';
                // Remove required attribute
                doctorFields.querySelectorAll('input, select').forEach(field => {
                    field.removeAttribute('required');
                });
            }
        });
    }
});
