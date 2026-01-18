<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_GET['bill_id'])) die("Invalid Bill ID");
$bill_id = intval($_GET['bill_id']);

// Fetch Bill Details with Hospital Branding logic
$sql = "SELECT b.*, u.username as patient_username, p.name as patient_name, u.email,
        r.name as doctor_name, a.department, p.patient_code,
        pr.medicine_details, pr.prescription_date
        FROM billing b 
        LEFT JOIN users u ON b.patient_id = u.user_id 
        LEFT JOIN patient_profiles p ON u.user_id = p.user_id
        LEFT JOIN users ud ON b.doctor_id = ud.user_id
        LEFT JOIN registrations r ON ud.registration_id = r.registration_id
        LEFT JOIN appointments a ON b.appointment_id = a.appointment_id
        LEFT JOIN prescriptions pr ON b.reference_id = pr.prescription_id AND b.bill_type='Pharmacy'
        WHERE b.bill_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$bill = $stmt->get_result()->fetch_assoc();

if(!$bill) die("Bill not found");

$inv_no = "INV-" . str_pad($bill['bill_id'], 4, '0', STR_PAD_LEFT);
$date = date('d M, Y', strtotime($bill['bill_date']));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Receipt #<?php echo $inv_no; ?></title>
    <style>
        body { font-family: 'Helvetica', sans-serif; padding: 40px; background: #f0f2f5; }
        .receipt { max-width: 800px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 30px; }
        .logo h1 { margin: 0; color: #1e293b; font-size: 28px; }
        .logo p { margin: 5px 0 0; color: #64748b; font-size: 14px; }
        .inv-info { text-align: right; }
        .inv-info h2 { margin: 0; color: #10b981; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; }
        .label { font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
        .val { font-size: 16px; font-weight: 600; color: #334155; }
        .items { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items th { background: #f8fafc; text-align: left; padding: 15px; color: #64748b; font-size: 12px; text-transform: uppercase; }
        .items td { padding: 15px; border-bottom: 1px solid #eee; color: #334155; }
        .total { text-align: right; margin-top: 20px; padding-top: 20px; border-top: 2px solid #eee; }
        .total h3 { font-size: 24px; color: #1e293b; margin: 0; }
        .status-paid { color: #10b981; border: 2px solid #10b981; padding: 5px 15px; display: inline-block; font-weight: 700; transform: rotate(-5deg); border-radius: 4px; }
        .footer { margin-top: 50px; text-align: center; color: #94a3b8; font-size: 12px; }
    </style>
</head>
<body onload="window.print()">
    <div class="receipt">
        <div class="header">
            <div class="logo">
                <h1>+ HEALCARE</h1>
                <p>Kanjirapally, Kottayam, Kerala<br>Phone: (+91) 9876543210</p>
            </div>
            <div class="inv-info">
                <?php if($bill['payment_status'] == 'Paid'): ?>
                    <div class="status-paid">PAID</div>
                <?php else: ?>
                    <h2 style="color: #f59e0b;">UNPAID</h2>
                <?php endif; ?>
                <p>Receipt #: <?php echo $inv_no; ?></p>
                <p>Date: <?php echo $date; ?></p>
            </div>
        </div>

        <div class="grid">
            <div>
                <div class="label">Billed To</div>
                <div class="val"><?php echo htmlspecialchars($bill['patient_name']); ?></div>
                <div style="color: #64748b; font-size: 14px; margin-top: 5px;">ID: <?php echo $bill['patient_code'] ?? 'N/A'; ?></div>
            </div>
            <div>
                <div class="label">Consulted Doctor</div>
                <div class="val">Dr. <?php echo htmlspecialchars($bill['doctor_name']); ?></div>
                <div style="color: #64748b; font-size: 14px; margin-top: 5px;"><?php echo htmlspecialchars($bill['department']); ?></div>
            </div>
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?php echo $bill['bill_type']; ?> Charges</strong><br>
                        <?php if($bill['bill_type'] == 'Pharmacy' && !empty($bill['medicine_details'])): ?>
                            <small style="color: #64748b; line-height: 1.5; display: block; margin-top: 5px;">
                                <?php echo nl2br(htmlspecialchars($bill['medicine_details'])); ?>
                            </small>
                        <?php else: ?>
                            <span style="color: #64748b;">Professional Services / Consultation</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right; vertical-align: top;">₹<?php echo number_format($bill['total_amount'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="total">
            <div class="label">Total Amount</div>
            <h3>₹<?php echo number_format($bill['total_amount'], 2); ?></h3>
        </div>

        <div class="footer">
            <p>Thank you for choosing HealCare.<br>This is a computer-generated receipt.</p>
        </div>
    </div>
</body>
</html>
