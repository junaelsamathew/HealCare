/**
 * Login Page Logic
 */

let selectedRole = 'patient';

function selectRole(role) {
    selectedRole = role;

    // Update UI
    document.getElementById('roleSelection').classList.remove('active');
    document.getElementById('authStep').classList.add('active');

    // Update Badge
    const badge = document.getElementById('selectedRoleBadge');
    badge.textContent = role.charAt(0).toUpperCase() + role.slice(1);
    badge.className = `role-badge badge-${role}`;

    // Show/Hide Doctor specific fields
    const docFields = document.getElementById('doctorFields');
    if (role === 'doctor') {
        docFields.style.display = 'block';
    } else {
        docFields.style.display = 'none';
    }
}

function showRoles() {
    document.getElementById('authStep').classList.remove('active');
    document.getElementById('roleSelection').classList.add('active');
}

// Tab Switching (Reuse logic or keep simple here)
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.tab-btn');
    const forms = document.querySelectorAll('.auth-form');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            forms.forEach(f => f.classList.remove('active'));

            tab.classList.add('active');
            const target = tab.getAttribute('data-tab');
            if (target === 'login') {
                document.getElementById('loginForm').classList.add('active');
            } else {
                document.getElementById('signupForm').classList.add('active');
            }
        });
    });
});
