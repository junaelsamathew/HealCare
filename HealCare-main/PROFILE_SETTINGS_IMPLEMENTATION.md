# Profile Settings Implementation

## Overview
Implemented profile settings for Doctors and Staff, allowing them to:
1.  **Edit Personal Details**: Update Phone Number and Address (which propagate to the `registrations` table).
2.  **Change Password**: Securely update their login password with current password verification.
3.  **View Professional Info**: See read-only professional details like specialization, designation, etc.

## Files Created/Modified

### 1. `profile_handler.php` (New)
*   **Purpose**: Central backend handler for profile actions.
*   **Actions**:
    *   `update_password`: Verifies current password, checks for match, hashes new password, and updates `users` table.
    *   `update_profile`: Updates phone and address in the `registrations` table.
*   **Security**: Uses `password_verify` and `password_hash`. Checks session role.

### 2. `doctor_settings.php` (Updated)
*   **Changes**:
    *   Fetched dynamic data (phone, address, email) from `registrations` table using `user_id`.
    *   Converted static "Personal Information" inputs into a `<form>` submitting to `profile_handler.php` (`action="update_profile"`).
    *   Added a fully functional "Account Security" section with a password change form (`action="update_password"`).

### 3. `staff_settings.php` (New)
*   **Purpose**: Shared settings page for all staff roles (Nurse, Receptionist, Pharmacist, Lab Tech, Canteen).
*   **Features**:
    *   Displays Staff Name, Role, Designation.
    *   Form to update Phone and Address.
    *   Form to change Password.
    *   "Back to Dashboard" link using simple JavaScript history back (since dashboards vary).

### 4. Staff Dashboards (Updated)
*   Added a "Profile Settings" link to the sidebar in:
    *   `staff_nurse_dashboard.php`
    *   `staff_receptionist_dashboard.php`
    *   `staff_pharmacist_dashboard.php`
    *   `staff_lab_staff_dashboard.php`
    *   `staff_canteen_staff_dashboard.php`

## Usage
*   **Doctors**: Log in -> Click "Profile Settings" in sidebar -> Edit details or change password.
*   **Staff**: Log in -> Click "Profile Settings" in sidebar -> Edit details or change password.
