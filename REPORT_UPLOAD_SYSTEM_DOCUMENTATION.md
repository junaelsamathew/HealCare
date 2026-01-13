# Role-Based Report Upload System - Documentation

## Overview
The HealCare Hospital Management System now includes a comprehensive role-based report upload system that allows staff members to upload reports related to their responsibilities. All uploaded reports automatically appear in the Admin Dashboard for review and analysis.

## System Components

### 1. Database Structure

#### `manual_reports` Table
- **report_id**: Auto-increment primary key
- **user_id**: Foreign key to users table
- **user_role**: Role of the uploader (canteen_staff, lab_staff, pharmacist, etc.)
- **report_type**: Specific type of report (e.g., "Daily Sales Report", "Monthly Revenue Report")
- **report_category**: Auto-assigned category based on role
- **department**: Department/category name
- **report_title**: User-provided title for the report
- **file_path**: Path to the uploaded PDF file
- **report_date**: Date the report pertains to
- **file_size**: Size of the uploaded file in bytes
- **status**: Report status (Submitted, Pending, Reviewed, Approved)
- **admin_notes**: Optional notes from admin
- **created_at**: Timestamp of upload

#### `report_analytics` Table (Future Enhancement)
- For storing extracted metrics from PDF reports
- Allows for automated data analysis

### 2. Role-Specific Report Types

#### Canteen Staff
- Daily Sales Report
- Monthly Revenue Report
- Inventory Report
- Menu Performance Report

#### Laboratory Staff
- Daily Test Report
- Monthly Lab Revenue
- Test Type Analysis
- Equipment Utilization

#### Pharmacist
- Daily Sales Report
- Monthly Pharmacy Revenue
- Inventory & Stock Report
- Prescription Analysis

#### Receptionist / Front Desk
- Daily Appointment Report
- Patient Visit Report
- Appointment Analytics
- Front Desk Summary

#### Nurse / Department Staff
- Department Activity Report
- Shift Report
- Patient Care Summary
- Department Revenue

#### Doctor
- Consultation Summary
- Patient Treatment Report
- Diagnosis Statistics
- Performance Report

#### Admin
- Overall Revenue Report
- Department Performance
- Payment Mode Analysis
- Custom Report

### 3. File Structure

```
HealCare/
├── includes/
│   └── report_upload_modal.php          # Reusable upload modal component
├── uploads/
│   └── reports/                         # Uploaded PDF files storage
├── setup_enhanced_reports.php           # Database setup script
├── upload_report_handler_v2.php         # Enhanced upload handler
├── admin_dashboard.php                  # Admin dashboard with reports section
├── reports_manager.php                  # Existing reports manager
└── staff_*_dashboard.php                # Staff dashboards with upload capability
```

### 4. Key Features

#### For Staff Members
1. **Easy Upload Interface**: Modal-based upload with role-specific report types
2. **Guided Selection**: Pre-defined report types based on user role
3. **PDF-Only Uploads**: Ensures standardized format
4. **Automatic Categorization**: Reports are auto-categorized by role
5. **Upload Confirmation**: Immediate feedback with report ID

#### For Admin
1. **Centralized Dashboard**: View all uploaded reports in one place
2. **Advanced Filtering**: Search by title, uploader, category, or status
3. **Visual Analytics**: Charts showing report distribution by category
4. **Statistics Cards**: Quick overview of total, pending, and monthly reports
5. **Download & View**: Direct PDF viewing and downloading
6. **Status Management**: Track report review status

### 5. Implementation Guide

#### Adding Report Upload to a Staff Dashboard

1. **Include the Modal Component** (at the end of the file, before `</body>`):
```php
<?php 
// Set staff_type for the modal
$staff_type = 'pharmacist'; // Change based on dashboard
include 'includes/report_upload_modal.php'; 
?>
```

2. **Add Upload Button** (anywhere in the dashboard):
```html
<button onclick="openReportModal()" class="btn btn-primary">
    <i class="fas fa-upload"></i> Upload Report
</button>
```

3. **Example Implementation** (see `staff_pharmacist_dashboard.php`):
- Added upload button in the Quick Archive section
- Included modal component at the end
- Set appropriate staff_type variable

#### Accessing the Admin Dashboard

1. Navigate to: `admin_dashboard.php?section=uploaded-reports`
2. Or click "Uploaded Reports" in the sidebar under "Reports & Analytics"

### 6. Usage Workflow

#### Staff Member Workflow
1. Click "Upload Report" button on their dashboard
2. Select appropriate report type from dropdown
3. Enter report title and date
4. Add optional notes
5. Upload PDF file (max 10MB recommended)
6. Submit report
7. Receive confirmation with Report ID

#### Admin Workflow
1. Access "Uploaded Reports" section in admin dashboard
2. View statistics and category breakdown
3. Use filters to find specific reports
4. Click "View" to open PDF in new tab
5. Click "Download" to save locally
6. Update status as needed (future enhancement)

### 7. Security Features

- **Authentication Required**: All upload endpoints check for logged-in users
- **Role Validation**: System validates user role before allowing uploads
- **File Type Restriction**: Only PDF files are accepted
- **File Size Validation**: Prevents excessively large uploads
- **Path Sanitization**: File names are sanitized to prevent directory traversal
- **Database Validation**: All inputs are escaped before database insertion

### 8. Future Enhancements

1. **PDF Data Extraction**: Automatically extract key metrics from uploaded PDFs
2. **Status Workflow**: Admin approval/rejection workflow
3. **Email Notifications**: Notify admin when new reports are uploaded
4. **Report Templates**: Provide downloadable templates for each report type
5. **Scheduled Reports**: Remind staff to upload monthly reports
6. **Analytics Dashboard**: Aggregate data from all uploaded reports
7. **Export Functionality**: Export report lists to Excel/CSV
8. **Version Control**: Track multiple versions of the same report

### 9. Troubleshooting

#### Upload Fails
- Check file is PDF format
- Verify file size is reasonable (<10MB)
- Ensure `uploads/reports/` directory exists and is writable
- Check database connection

#### Modal Doesn't Open
- Verify `includes/report_upload_modal.php` is included
- Check JavaScript console for errors
- Ensure Font Awesome icons are loaded

#### Reports Don't Appear in Admin Dashboard
- Verify database insert was successful
- Check that report status is set correctly
- Refresh the admin dashboard page
- Check database query for errors

### 10. Database Setup

Run the setup script to create/update tables:
```bash
php setup_enhanced_reports.php
```

Or access via browser:
```
http://localhost/HealCare/setup_enhanced_reports.php
```

### 11. API Endpoints

#### Upload Handler
- **File**: `upload_report_handler_v2.php`
- **Method**: POST
- **Parameters**:
  - `report_type`: Type of report
  - `report_title`: Title of report
  - `report_date`: Date of report
  - `description`: Optional notes
  - `report_file`: PDF file upload

#### Response
- Success: Redirects to appropriate dashboard with success message
- Failure: JavaScript alert with error message

### 12. Customization

#### Adding New Report Types
Edit `includes/report_upload_modal.php`:
```php
$role_report_types = [
    'your_role' => [
        'New Report Type' => 'Description of the report',
        // Add more types...
    ]
];
```

#### Changing Report Categories
Edit `upload_report_handler_v2.php`:
```php
$report_categories = [
    'your_role' => 'Your Category Name',
    // Add more mappings...
];
```

### 13. Best Practices

1. **Regular Uploads**: Encourage staff to upload reports monthly
2. **Consistent Naming**: Use clear, descriptive report titles
3. **Proper Categorization**: Select the most appropriate report type
4. **File Organization**: Keep uploaded files organized by date
5. **Regular Review**: Admin should review reports regularly
6. **Backup**: Regularly backup the `uploads/reports/` directory

### 14. Support

For issues or questions:
1. Check this documentation
2. Review error messages in browser console
3. Check PHP error logs
4. Verify database connectivity
5. Ensure all files are properly uploaded to server

---

**Version**: 1.0  
**Last Updated**: January 2026  
**Author**: HealCare Development Team
