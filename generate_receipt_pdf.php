<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_GET['bill_id']) || empty($_GET['bill_id'])) {
    die("Invalid Bill ID.");
}

$bill_id = intval($_GET['bill_id']);
$user_id = $_SESSION['user_id'];

// Fetch Bill Details
$sql = "
    SELECT b.*, 
           doc_reg.name as doctor_name, doc.specialization,
           pat_reg.name as patient_name, pat_reg.email as patient_email, pp.address, pp.phone,
           pay.transaction_id, pay.payment_date as paid_date
    FROM billing b
    LEFT JOIN users doc_u ON b.doctor_id = doc_u.user_id
    LEFT JOIN registrations doc_reg ON doc_u.registration_id = doc_reg.registration_id
    LEFT JOIN doctors doc ON doc_u.user_id = doc.user_id
    LEFT JOIN users pat_u ON b.patient_id = pat_u.user_id
    LEFT JOIN registrations pat_reg ON pat_u.registration_id = pat_reg.registration_id
    LEFT JOIN patient_profiles pp ON b.patient_id = pp.user_id
    LEFT JOIN payments pay ON b.bill_id = pay.bill_id
    WHERE b.bill_id = ?
";

// If patient, restrict to own bills
if ($_SESSION['user_role'] == 'patient') {
    $sql .= " AND b.patient_id = $user_id";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    die("Receipt not found or access denied.");
}

$data = $res->fetch_assoc();

$invoice_no = "INV-" . str_pad($data['bill_id'], 5, "0", STR_PAD_LEFT);
$bill_date = date('d M, Y', strtotime($data['bill_date']));
$paid_date = $data['paid_date'] ? date('d M, Y', strtotime($data['paid_date'])) : $bill_date;
$txn_id = $data['transaction_id'] ?? 'CASH-' . rand(10000,99999);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - <?php echo $invoice_no; ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Courier+Prime:wght@400;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #e2e8f0;
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            padding: 40px;
        }
        .receipt-container {
            background: white;
            width: 180mm; /* Slightly narrower than A4 */
            min-height: 200mm;
            padding: 15mm;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px dashed #cbd5e1;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .brand h1 {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.5px;
        }
        .brand p {
            font-size: 11px;
            color: #64748b;
            margin: 5px 0 0;
        }
        .invoice-meta {
            text-align: right;
        }
        .tag {
            background: #dcfce7;
            color: #166534;
            font-size: 10px;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 4px;
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 5px;
        }
        .meta-row {
            font-size: 12px;
            color: #475569;
            margin-top: 4px;
        }
        .bill-to {
            margin-bottom: 30px;
        }
        .bill-to h4 {
            font-size: 10px;
            text-transform: uppercase;
            color: #94a3b8;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        .patient-info p {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
        }
        .patient-info span {
            font-size: 12px;
            color: #64748b;
            font-weight: 400;
        }
        .line-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .line-items th {
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            color: #64748b;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .line-items td {
            padding: 15px 0;
            font-size: 14px;
            color: #1e293b;
            border-bottom: 1px solid #f1f5f9;
        }
        .total-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 40px;
        }
        .total-box {
            width: 200px;
        }
        .t-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin-bottom: 8px;
            color: #64748b;
        }
        .t-row.final {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            border-top: 2px solid #e2e8f0;
            padding-top: 10px;
            margin-top: 10px;
        }
        .footer {
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            margin-top: 50px;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
        }
        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
        }
        .btn-download {
            background: #0f172a;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="downloadPDF()" class="btn-download">Download Receipt</button>
    </div>

    <div class="receipt-container" id="receiptContent">
        <div class="header">
            <div class="brand">
                <h1>HEALCARE</h1>
                <p>Medical Center & Hospital</p>
                <p>Kanjirapally, Kottayam - 686507</p>
            </div>
            <div class="invoice-meta">
                <div class="tag">PAID RECEIPT</div>
                <div class="meta-row"><strong>Receipt #:</strong> <?php echo $invoice_no; ?></div>
                <div class="meta-row"><strong>Date:</strong> <?php echo $paid_date; ?></div>
                <div class="meta-row"><strong>Txn ID:</strong> <span style="font-family:'Courier Prime';"><?php echo $txn_id; ?></span></div>
            </div>
        </div>

        <div class="bill-to">
            <h4>Billed To</h4>
            <div class="patient-info">
                <p><?php echo htmlspecialchars($data['patient_name']); ?></p>
                <span><?php echo htmlspecialchars($data['phone']); ?></span><br>
                <span><?php echo htmlspecialchars($data['address']); ?></span>
            </div>
        </div>

        <table class="line-items">
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th style="width: 25%;">Provider</th>
                    <th style="width: 25%; text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($data['bill_type']); ?> Fee</strong><br>
                        <span style="font-size: 11px; color: #64748b;">Professional medical services</span>
                    </td>
                    <td>Dr. <?php echo htmlspecialchars($data['doctor_name']); ?><br><span style="font-size: 10px; color: #94a3b8;"><?php echo htmlspecialchars($data['specialization']); ?></span></td>
                    <td style="text-align: right; font-weight: 600;">₹<?php echo number_format($data['total_amount'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-box">
                <div class="t-row">
                    <span>Subtotal</span>
                    <span>₹<?php echo number_format($data['total_amount'], 2); ?></span>
                </div>
                <!-- <div class="t-row">
                    <span>Tax (0%)</span>
                    <span>₹0.00</span>
                </div> -->
                <div class="t-row final">
                    <span>Total Paid</span>
                    <span>₹<?php echo number_format($data['total_amount'], 2); ?></span>
                </div>
                <div style="text-align: right; font-size: 11px; color: #64748b; margin-top: 5px;">
                    Paid via <?php echo htmlspecialchars($data['payment_mode'] ?? 'Cash'); ?>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Thank you for choosing HealCare. For questions, please contact billing@healcare.com</p>
            <p>This is a computer-generated receipt.</p>
        </div>
    </div>

    <script>
        function downloadPDF() {
            const element = document.getElementById('receiptContent');
            const opt = {
                margin:       0,
                filename:     'Receipt_<?php echo $invoice_no; ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'mm', format: [210, 240], orientation: 'portrait' } 
                // Custom height to fit content nicely
            };
            html2pdf().set(opt).from(element).save();
        }

        window.onload = function() {
            setTimeout(downloadPDF, 800);
        };
    </script>
</body>
</html>
