<?php
// Report Upload Modal Component
// Include this in any dashboard to enable report uploads

// Define role-specific report types
$role_report_types = [
    'canteen_staff' => [
        'Daily Sales Report' => 'Daily food sales and revenue',
        'Payment Collection Report' => 'Cash vs UPI revenue analysis',
        'Stock Usage Report' => 'Inventory usage and wastage',
        'Item-Wise Sales Report' => 'Popular items and menu performance'
    ],
    'lab_staff' => [
        'Daily Test Report' => 'Daily diagnostic tests performed',
        'Monthly Lab Revenue' => 'Monthly laboratory revenue',
        'Test Type Analysis' => 'Breakdown by test categories',
        'Equipment Utilization' => 'Lab equipment usage report'
    ],
    'pharmacist' => [
        'Medicine Sales Report' => 'Daily and monthly medicine sales',
        'Stock Usage / Remaining Stock Report' => 'Inventory tracking and remaining stock',
        'Expiry Alert Report' => 'Medicines nearing expiration dates'
    ],
    'receptionist' => [
        'Appointment Booking Report' => 'Details of scheduled appointments',
        'Patient Registration Report' => 'New patient registrations summary',
        'Daily Check-In / Check-Out Report' => 'Patient flow tracking'
    ],
    'nurse' => [
        'Vital Signs Monitoring Report' => 'Patient vitals logs',
        'Patient Care / Duty Report' => 'Daily nursing care and duty logs'
    ],
    'doctor' => [
        'Consultation Summary' => 'Daily/monthly consultation data',
        'Patient Treatment Report' => 'Treatment outcomes and follow-ups',
        'Diagnosis Statistics' => 'Common diagnoses and trends',
        'Performance Report' => 'Doctor performance metrics'
    ],
    'admin' => [
        'Overall Revenue Report' => 'Hospital-wide revenue',
        'Department Performance' => 'All departments performance',
        'Payment Mode Analysis' => 'Payment methods breakdown',
        'Custom Report' => 'Custom administrative report'
    ]
];

// Get current user's role
$current_role = $_SESSION['user_role'] ?? 'staff';
if ($current_role == 'staff' && isset($staff_type)) {
    $current_role = $staff_type;
}

$available_types = $role_report_types[$current_role] ?? [];
?>

<!-- Report Upload Modal -->
<div id="reportUploadModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); width: 600px; max-width: 90%; padding: 40px; border-radius: 24px; border: 1px solid rgba(59, 130, 246, 0.3); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.7);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h3 style="font-size: 24px; font-weight: 700; color: #f8fafc; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-file-upload" style="color: #3b82f6;"></i>
                Upload Report
            </h3>
            <button onclick="closeReportModal()" style="background: none; border: none; color: #94a3b8; font-size: 24px; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; transition: 0.3s;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form action="upload_report_handler_v2.php" method="POST" enctype="multipart/form-data" id="reportUploadForm">
            <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
            <!-- Report Type Selection -->
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                    Report Type <span style="color: #ef4444;">*</span>
                </label>
                <select name="report_type" required style="width: 100%; background: #020617; border: 1px solid rgba(255,255,255,0.1); padding: 14px 16px; border-radius: 12px; color: #f8fafc; font-size: 14px; outline: none; transition: 0.3s;" onchange="updateReportDescription(this)">
                    <option value="">Select report type...</option>
                    <?php foreach ($available_types as $type => $desc): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" data-desc="<?php echo htmlspecialchars($desc); ?>">
                            <?php echo htmlspecialchars($type); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small id="reportTypeDesc" style="display: block; margin-top: 8px; color: #64748b; font-size: 12px; font-style: italic;"></small>
            </div>

            <!-- Report Title -->
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                    Report Title <span style="color: #ef4444;">*</span>
                </label>
                <input type="text" name="report_title" required placeholder="e.g., January 2026 Monthly Sales Summary" style="width: 100%; background: #020617; border: 1px solid rgba(255,255,255,0.1); padding: 14px 16px; border-radius: 12px; color: #f8fafc; font-size: 14px; outline: none; transition: 0.3s;">
            </div>

            <!-- Report Date -->
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                    Report Date <span style="color: #ef4444;">*</span>
                </label>
                <input type="date" name="report_date" value="<?php echo date('Y-m-d'); ?>" required style="width: 100%; background: #020617; border: 1px solid rgba(255,255,255,0.1); padding: 14px 16px; border-radius: 12px; color: #f8fafc; font-size: 14px; outline: none; transition: 0.3s;">
            </div>

            <!-- Description (Optional) -->
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                    Additional Notes (Optional)
                </label>
                <textarea name="description" rows="3" placeholder="Add any additional context or notes..." style="width: 100%; background: #020617; border: 1px solid rgba(255,255,255,0.1); padding: 14px 16px; border-radius: 12px; color: #f8fafc; font-size: 14px; outline: none; transition: 0.3s; resize: vertical;"></textarea>
            </div>

            <!-- File Upload -->
            <div style="margin-bottom: 32px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                    PDF Document <span style="color: #ef4444;">*</span>
                </label>
                <div style="position: relative;">
                    <input type="file" name="report_file" accept=".pdf" required id="pdfFileInput" style="display: none;" onchange="updateFileName(this)">
                    <div onclick="document.getElementById('pdfFileInput').click()" style="width: 100%; border: 2px dashed rgba(59, 130, 246, 0.3); padding: 40px; text-align: center; border-radius: 12px; background: rgba(59, 130, 246, 0.05); cursor: pointer; transition: 0.3s;" onmouseover="this.style.borderColor='#3b82f6'; this.style.background='rgba(59, 130, 246, 0.1)'" onmouseout="this.style.borderColor='rgba(59, 130, 246, 0.3)'; this.style.background='rgba(59, 130, 246, 0.05)'">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #3b82f6; margin-bottom: 16px; display: block;"></i>
                        <p style="color: #f8fafc; font-weight: 600; margin-bottom: 8px;" id="fileNameDisplay">Click to browse or drag PDF file here</p>
                        <p style="color: #64748b; font-size: 12px;">Maximum file size: 10MB</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div style="display: flex; gap: 12px;">
                <button type="submit" style="flex: 1; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 14px 24px; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.3s; box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4);">
                    <i class="fas fa-upload"></i>
                    Submit Report
                </button>
                <button type="button" onclick="closeReportModal()" style="flex: 1; background: transparent; border: 1px solid rgba(255,255,255,0.1); color: #f8fafc; padding: 14px 24px; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.3s;">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openReportModal(viewType = '') {
    const modal = document.getElementById('reportUploadModal');
    modal.style.display = 'flex';
    
    // Auto-select report type based on current view
    const viewMap = {
        'canteen_daily_sales': 'Daily Sales Report',
        'canteen_item_sales': 'Item-Wise Sales Report',
        'canteen_payments': 'Payment Collection Report',
        'canteen_stock': 'Stock Usage Report',
        'consultation_revenue': 'Consultation Summary',
        'appointment_report': 'Daily Appointment Report',
        'lab_revenue': 'Monthly Lab Revenue', 
        'pharmacy_sales': 'Daily Sales Report',
        // Nurse Reports
        'nurse_vitals': 'Vital Signs Monitoring Report',
        'nurse_care': 'Patient Care / Duty Report'
    };

    if (viewType && viewMap[viewType]) {
        const select = modal.querySelector('select[name="report_type"]');
        if (select) {
            select.value = viewMap[viewType];
            // Trigger change event to update description
            const event = new Event('change');
            select.dispatchEvent(event);
        }
    }
}

function closeReportModal() {
    document.getElementById('reportUploadModal').style.display = 'none';
    document.getElementById('reportUploadForm').reset();
    document.getElementById('fileNameDisplay').textContent = 'Click to browse or drag PDF file here';
    document.getElementById('reportTypeDesc').textContent = '';
}

function updateReportDescription(select) {
    const desc = select.options[select.selectedIndex].getAttribute('data-desc');
    document.getElementById('reportTypeDesc').textContent = desc || '';
}

function updateFileName(input) {
    const fileName = input.files[0]?.name || 'Click to browse or drag PDF file here';
    document.getElementById('fileNameDisplay').textContent = fileName;
}

// Close modal when clicking outside
document.getElementById('reportUploadModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeReportModal();
    }
});
</script>

<style>
#reportUploadModal input:focus,
#reportUploadModal select:focus,
#reportUploadModal textarea:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

#reportUploadModal button[type="submit"]:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
}

#reportUploadModal button[type="button"]:hover {
    background: rgba(255,255,255,0.05);
}
</style>
