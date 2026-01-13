# Quick Implementation Guide
## Adding Report Upload to Staff Dashboards

This guide shows how to add the report upload functionality to any staff dashboard in under 5 minutes.

## Step 1: Add the Modal Include

At the **end of your dashboard file**, just before `</body>`, add:

```php
<?php 
// Set staff_type for the modal (change based on your dashboard)
$staff_type = 'canteen_staff';  // Options: canteen_staff, lab_staff, pharmacist, receptionist, nurse
include 'includes/report_upload_modal.php'; 
?>
```

## Step 2: Add Upload Button

Add this button anywhere in your dashboard where you want users to upload reports:

```html
<button onclick="openReportModal()" class="btn btn-primary">
    <i class="fas fa-upload"></i> Upload Report
</button>
```

Or as a styled link:

```html
<a href="javascript:openReportModal()" style="background: #3b82f6; color: white; padding: 10px 20px; border-radius: 10px; text-decoration: none; display: inline-block;">
    <i class="fas fa-upload"></i> Upload Report
</a>
```

## Complete Example for Canteen Staff Dashboard

```php
<?php
session_start();
include 'includes/db_connect.php';
// ... your existing code ...
?>
<!DOCTYPE html>
<html>
<head>
    <!-- Your existing head content -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Your existing dashboard content -->
    
    <div class="upload-section">
        <h3>Upload Revenue Report</h3>
        <p>Submit your daily or monthly canteen sales report</p>
        <button onclick="openReportModal()" class="btn btn-primary">
            <i class="fas fa-upload"></i> Upload Report
        </button>
    </div>
    
    <!-- Add this at the end, before </body> -->
    <?php 
    $staff_type = 'canteen_staff';
    include 'includes/report_upload_modal.php'; 
    ?>
</body>
</html>
```

## Staff Type Mapping

Use the correct `$staff_type` value for each dashboard:

| Dashboard | staff_type Value |
|-----------|-----------------|
| Canteen Staff Dashboard | `'canteen_staff'` |
| Laboratory Staff Dashboard | `'lab_staff'` |
| Pharmacist Dashboard | `'pharmacist'` |
| Receptionist Dashboard | `'receptionist'` |
| Nurse Dashboard | `'nurse'` |
| Doctor Dashboard | `'doctor'` |
| Admin Dashboard | `'admin'` |

## That's It!

The modal will automatically:
- Show role-specific report types
- Handle file uploads
- Validate PDF files
- Save to database
- Redirect back to dashboard with confirmation

## Testing

1. Open your staff dashboard
2. Click the "Upload Report" button
3. Select a report type
4. Fill in the form
5. Upload a PDF file
6. Submit
7. Check admin dashboard to see your uploaded report

## Troubleshooting

**Modal doesn't appear?**
- Make sure you included the modal file
- Check that Font Awesome is loaded
- Open browser console for JavaScript errors

**Upload fails?**
- Ensure file is PDF format
- Check file size (keep under 10MB)
- Verify `uploads/reports/` directory exists and is writable

**Need help?**
See `REPORT_UPLOAD_SYSTEM_DOCUMENTATION.md` for detailed information.
