# Database Error Fix - Manual Reports Table

## Problem
Fatal error on line 2683 of `admin_dashboard.php`:
```
mysqli_sql_exception: Unknown column 'status' in 'where clause'
```

## Root Cause
The `manual_reports` table was created using an older schema (`setup_reports_db.php`) that didn't include the `status` column and several other important columns. When the enhanced version (`setup_enhanced_reports.php`) was created later, it used `CREATE TABLE IF NOT EXISTS`, which doesn't alter existing tables.

## Missing Columns
The following columns were missing from the `manual_reports` table:
- `report_type` - Type of report (e.g., "Daily Report", "Monthly Report")
- `report_category` - Category for filtering (e.g., "Revenue", "Appointments")
- `file_size` - Size of uploaded file in bytes
- `status` - Report status (Pending, Submitted, Reviewed, Approved)
- `admin_notes` - Notes from admin review

## Solution
Created a migration script (`migrate_manual_reports.php`) that:
1. Checks if each required column exists in the table
2. Adds missing columns with appropriate default values
3. Preserves existing data

## Files Created
- `migrate_manual_reports.php` - Migration script to add missing columns
- `test_reports_fix.php` - Test script to verify the fix

## Verification
All queries in the admin dashboard now execute successfully:
- Total reports count: ✓
- Pending reports count: ✓
- Submitted reports count: ✓
- This month reports count: ✓

## How to Use
If you encounter this error again on a different database:
```bash
C:\xampp\php\php.exe migrate_manual_reports.php
```

The admin dashboard should now load without errors at:
`http://localhost/HealCare/admin_dashboard.php?section=uploaded-reports`
