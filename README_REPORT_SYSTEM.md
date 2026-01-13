# 📊 Role-Based Report Upload System

## Overview

A comprehensive report management system for HealCare Hospital that enables staff members to upload role-specific reports and provides administrators with a centralized dashboard for review and analysis.

![System Architecture](See IMPLEMENTATION_SUMMARY.md for architecture diagram)

## 🎯 Key Features

### For Staff Members
- ✅ **Role-Specific Report Types** - Each role has predefined report categories
- ✅ **Easy Upload Interface** - Modern modal-based upload system
- ✅ **PDF-Only Uploads** - Standardized format for all reports
- ✅ **Automatic Categorization** - Reports auto-categorized by role
- ✅ **Instant Confirmation** - Immediate feedback with Report ID

### For Administrators
- ✅ **Centralized Dashboard** - View all reports in one place
- ✅ **Visual Analytics** - Charts and graphs for data insights
- ✅ **Advanced Filtering** - Search by title, category, status, uploader
- ✅ **Statistics Overview** - Quick metrics on uploaded reports
- ✅ **Download & View** - Direct PDF access and downloads

## 🚀 Quick Start

### 1. Database Setup
```bash
# Navigate to your HealCare directory
cd c:\xampp\htdocs\HealCare

# Run the setup script
c:\xampp\php\php.exe setup_enhanced_reports.php
```

### 2. Access Admin Dashboard
```
http://localhost/HealCare/admin_dashboard.php?section=uploaded-reports
```

### 3. Add Upload to Staff Dashboards

See `QUICK_IMPLEMENTATION_GUIDE.md` for step-by-step instructions.

**Example for Pharmacist Dashboard:**
```php
// At the end of staff_pharmacist_dashboard.php, before </body>
<?php 
$staff_type = 'pharmacist';
include 'includes/report_upload_modal.php'; 
?>
```

## 📋 Role-Specific Report Types

| Role | Report Types | Category |
|------|-------------|----------|
| **Canteen Staff** | Daily Sales, Monthly Revenue, Inventory, Menu Performance | Canteen Revenue |
| **Lab Staff** | Daily Tests, Monthly Revenue, Test Analysis, Equipment Utilization | Laboratory Revenue |
| **Pharmacist** | Daily Sales, Monthly Revenue, Inventory & Stock, Prescription Analysis | Pharmacy Sales |
| **Receptionist** | Daily Appointments, Patient Visits, Appointment Analytics, Front Desk Summary | Appointment & Patient Visit |
| **Nurse** | Department Activity, Shift Report, Patient Care, Department Revenue | Department Revenue |
| **Doctor** | Consultation Summary, Treatment Reports, Diagnosis Stats, Performance | Consultation Revenue |
| **Admin** | Overall Revenue, Department Performance, Payment Analysis, Custom | Administrative |

## 📁 File Structure

```
HealCare/
├── includes/
│   └── report_upload_modal.php          # Reusable upload modal
├── uploads/
│   └── reports/                         # PDF storage
├── setup_enhanced_reports.php           # Database setup
├── upload_report_handler_v2.php         # Upload processor
├── admin_dashboard.php                  # Admin dashboard (updated)
├── staff_pharmacist_dashboard.php       # Example implementation
├── IMPLEMENTATION_SUMMARY.md            # Complete feature list
├── REPORT_UPLOAD_SYSTEM_DOCUMENTATION.md # Full documentation
├── QUICK_IMPLEMENTATION_GUIDE.md        # Quick start guide
└── README_REPORT_SYSTEM.md             # This file
```

## 🎨 Screenshots

### Admin Dashboard - Uploaded Reports Section
- Statistics cards showing total, pending, submitted, and monthly reports
- Category breakdown chart (doughnut chart)
- Advanced filters (search, category, status)
- Comprehensive reports table with view/download options

### Staff Upload Modal
- Role-specific report type selection
- Report title and date inputs
- PDF file upload with drag-and-drop
- Clean, modern dark theme UI

## 💻 Usage Examples

### Staff Member Uploading a Report

1. **Open Dashboard** - Navigate to your role-specific dashboard
2. **Click Upload** - Click "Upload Report" button
3. **Select Type** - Choose from role-specific report types
4. **Fill Details** - Enter report title and date
5. **Upload PDF** - Select or drag PDF file
6. **Submit** - Click submit and receive confirmation

### Admin Reviewing Reports

1. **Access Dashboard** - Go to Admin Dashboard → Uploaded Reports
2. **View Statistics** - See overview of all uploaded reports
3. **Use Filters** - Search or filter by category/status
4. **Review Reports** - Click "View" to open PDF in new tab
5. **Download** - Click "Download" to save locally

## 🔐 Security Features

- ✅ **Authentication Required** - All endpoints check login status
- ✅ **Role Validation** - System validates user permissions
- ✅ **File Type Restriction** - Only PDF files accepted
- ✅ **File Size Limits** - Prevents excessive uploads
- ✅ **SQL Injection Prevention** - All inputs sanitized
- ✅ **Path Sanitization** - Prevents directory traversal

## 📊 Database Schema

### manual_reports Table
```sql
- report_id (INT, PRIMARY KEY, AUTO_INCREMENT)
- user_id (INT, FOREIGN KEY)
- user_role (VARCHAR)
- report_type (VARCHAR)
- report_category (VARCHAR)
- department (VARCHAR)
- report_title (VARCHAR)
- file_path (VARCHAR)
- report_date (DATE)
- file_size (INT)
- status (VARCHAR)
- admin_notes (TEXT)
- created_at (TIMESTAMP)
```

## 🛠️ Customization

### Adding New Report Types

Edit `includes/report_upload_modal.php`:
```php
$role_report_types = [
    'your_role' => [
        'New Report Type' => 'Description here',
    ]
];
```

### Changing Report Categories

Edit `upload_report_handler_v2.php`:
```php
$report_categories = [
    'your_role' => 'Your Category Name',
];
```

## 📚 Documentation

- **Full Documentation**: `REPORT_UPLOAD_SYSTEM_DOCUMENTATION.md`
- **Quick Start Guide**: `QUICK_IMPLEMENTATION_GUIDE.md`
- **Implementation Summary**: `IMPLEMENTATION_SUMMARY.md`

## 🐛 Troubleshooting

### Upload Fails
- ✓ Check file is PDF format
- ✓ Verify file size is reasonable (<10MB)
- ✓ Ensure `uploads/reports/` directory exists and is writable
- ✓ Check database connection

### Modal Doesn't Open
- ✓ Verify modal file is included
- ✓ Check JavaScript console for errors
- ✓ Ensure Font Awesome is loaded

### Reports Don't Appear
- ✓ Verify database insert was successful
- ✓ Check report status
- ✓ Refresh admin dashboard
- ✓ Check database query

## 🎯 Next Steps

1. **Add Upload to All Dashboards** - Follow QUICK_IMPLEMENTATION_GUIDE.md
2. **Test Each Role** - Upload sample reports from each role
3. **Review Admin Dashboard** - Check all reports appear correctly
4. **Customize Report Types** - Adjust based on hospital needs
5. **Train Staff** - Show staff how to upload reports

## 📈 Future Enhancements

- [ ] PDF data extraction and auto-analysis
- [ ] Admin approval workflow
- [ ] Email notifications for new uploads
- [ ] Downloadable report templates
- [ ] Scheduled upload reminders
- [ ] Aggregated analytics dashboard
- [ ] Export to Excel functionality
- [ ] Report version control

## ✅ Implementation Status

**Current Status**: ✅ COMPLETE AND READY TO USE

**Implemented**:
- ✅ Database structure
- ✅ Upload system
- ✅ Reusable modal component
- ✅ Admin dashboard integration
- ✅ Example staff dashboard (Pharmacist)
- ✅ Role-specific report types
- ✅ Security features
- ✅ Complete documentation

**Pending** (Easy to add):
- ⏳ Upload buttons in remaining staff dashboards
- ⏳ Custom report types per hospital needs

## 📞 Support

For help:
1. Check documentation files
2. Review code comments
3. See example in `staff_pharmacist_dashboard.php`
4. Check browser console for errors
5. Verify database connectivity

## 🏆 Credits

**Developed for**: HealCare Hospital Management System  
**Version**: 1.0  
**Date**: January 2026  
**Status**: Production Ready

---

**Ready to use immediately!** Follow the Quick Start guide to begin uploading reports.
