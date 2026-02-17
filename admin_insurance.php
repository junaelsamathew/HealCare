<?php
session_start();
include 'includes/db_connect.php';

// Auth Check (Admin Only)
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$msg = "";
if (isset($_POST['action']) && $_POST['action'] == 'update_claim') {
    $claim_id = (int)$_POST['claim_id'];
    $status = $_POST['status']; // Approved, Rejected
    $comments = mysqli_real_escape_string($conn, $_POST['comments']);

    if($conn->query("UPDATE insurance_claims SET status = '$status', admin_comments = '$comments' WHERE claim_id = $claim_id")) {
        $msg = "Claim #$claim_id Updated to $status";
        
        // If Rejected, the insurance amount on the bill should be reverted to patient payable?
        // This is complex logic. For now, we assume this is just an approval workflow.
        // Ideally: If Rejected -> Update Billing: insurance_amount = 0, patient_payable = total_amount.
        if ($status === 'Rejected') {
            // Fetch Bill ID
            $c_res = $conn->query("SELECT bill_id, covered_amount FROM insurance_claims WHERE claim_id = $claim_id");
            if($c_res && $c_res->num_rows > 0) {
                $c_data = $c_res->fetch_assoc();
                $bill_id = $c_data['bill_id'];
                $covered = $c_data['covered_amount'];
                
                // Revert bill
                $conn->query("UPDATE billing SET patient_payable_amount = patient_payable_amount + $covered, insurance_amount = 0 WHERE bill_id = $bill_id");
            }
        }
    } else {
        $msg = "Error updating claim: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insurance Claims - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0f172a; color: white; margin: 0; padding: 20px; }
        .container { max_width: 1200px; margin: 0 auto; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-back { color: #94a3b8; text-decoration: none; display: flex; align-items: center; gap: 5px; }
        
        .card { background: #1e293b; border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 20px; margin-bottom: 30px; }
        h2 { font-size: 20px; color: #fff; margin-top: 0; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; color: #64748b; padding: 15px; font-size: 13px; font-weight: 500; border-bottom: 1px solid rgba(255,255,255,0.05); }
        td { padding: 15px; font-size: 14px; border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: middle; }
        
        .status-pill { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .pill-Pending { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .pill-Approved { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .pill-Rejected { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        
        .btn-action { padding: 6px 12px; border-radius: 6px; border: none; font-size: 12px; cursor: pointer; color: #fff; margin-right: 5px; transaction: 0.2s; }
        .btn-approve { background: #10b981; }
        .btn-reject { background: #ef4444; }
        .btn-approve:hover { background: #059669; }
        .btn-reject:hover { background: #dc2626; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: #1e293b; padding: 30px; border-radius: 15px; width: 400px; border: 1px solid rgba(255,255,255,0.1); }
        textarea { width: 100%; background: #0f172a; border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 10px; border-radius: 8px; margin: 10px 0; }
    </style>
</head>
<body>

<div class="container">
    <div class="top-bar">
        <div>
            <a href="admin_dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <h1 style="margin: 10px 0 0;">Insurance Claims Management</h1>
        </div>
        <div style="text-align: right;">
            <?php 
            $pending_res = $conn->query("SELECT COUNT(*) as c FROM insurance_claims WHERE status = 'Pending'");
            $pending_count = $pending_res->fetch_assoc()['c'];
            ?>
            <span style="font-size: 32px; font-weight: 700; color: #f59e0b;"><?php echo $pending_count; ?></span>
            <div style="font-size: 12px; color: #94a3b8; text-transform: uppercase;">Pending Actions</div>
        </div>
    </div>

    <?php if($msg): ?>
        <div style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 15px; border-radius: 8px; margin-bottom: 30px; border: 1px solid rgba(59, 130, 246, 0.2);">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <!-- Pending Claims -->
    <div class="card">
        <h2><i class="fas fa-clock" style="color: #f59e0b; margin-right: 10px;"></i> Pending Claims</h2>
        <table>
            <thead>
                <tr>
                    <th>Claim ID</th>
                    <th>Date</th>
                    <th>Patient</th>
                    <th>Provider</th>
                    <th>Total Bill</th>
                    <th>Claim Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT c.*, b.bill_date, u.username, ip.provider_name, ip.policy_number, r.name as patient_name 
                        FROM insurance_claims c 
                        JOIN billing b ON c.bill_id = b.bill_id 
                        JOIN users u ON c.patient_id = u.user_id 
                        JOIN registrations r ON u.registration_id = r.registration_id
                        JOIN insurance_policies ip ON c.policy_id = ip.policy_id 
                        WHERE c.status = 'Pending' 
                        ORDER BY c.created_at DESC";
                $res = $conn->query($sql);
                
                if($res && $res->num_rows > 0):
                    while($row = $res->fetch_assoc()):
                ?>
                <tr>
                    <td>#CLM-<?php echo $row['claim_id']; ?></td>
                    <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($row['patient_name']); ?></strong><br>
                        <small style="color: #64748b;"><?php echo htmlspecialchars($row['policy_number']); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($row['provider_name']); ?></td>
                    <td>₹<?php echo number_format($row['total_bill_amount'], 2); ?></td>
                    <td style="color: #f59e0b; font-weight: 600;">₹<?php echo number_format($row['covered_amount'], 2); ?></td>
                    <td>
                        <button class="btn-action btn-approve" onclick="openAction(<?php echo $row['claim_id']; ?>, 'Approved')"><i class="fas fa-check"></i> Approve</button>
                        <button class="btn-action btn-reject" onclick="openAction(<?php echo $row['claim_id']; ?>, 'Rejected')"><i class="fas fa-times"></i> Reject</button>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="7" style="text-align: center; color: #64748b; padding: 30px;">No pending claims.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- History -->
    <div class="card">
        <h2><i class="fas fa-history" style="color: #3b82f6; margin-right: 10px;"></i> Claims History</h2>
        <table>
            <thead>
                <tr>
                    <th>Claim ID</th>
                    <th>Date</th>
                    <th>Patient</th>
                    <th>Provider</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Comments</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql_hist = "SELECT c.*, ip.provider_name, r.name as patient_name 
                        FROM insurance_claims c 
                        JOIN users u ON c.patient_id = u.user_id 
                        JOIN registrations r ON u.registration_id = r.registration_id
                        JOIN insurance_policies ip ON c.policy_id = ip.policy_id 
                        WHERE c.status != 'Pending' 
                        ORDER BY c.created_at DESC LIMIT 20";
                $res_hist = $conn->query($sql_hist);
                
                if($res_hist && $res_hist->num_rows > 0):
                    while($row = $res_hist->fetch_assoc()):
                ?>
                <tr>
                    <td>#CLM-<?php echo $row['claim_id']; ?></td>
                    <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['provider_name']); ?></td>
                    <td>₹<?php echo number_format($row['covered_amount'], 2); ?></td>
                    <td><span class="status-pill pill-<?php echo $row['status']; ?>"><?php echo $row['status']; ?></span></td>
                    <td style="color: #94a3b8; font-size: 12px;"><?php echo htmlspecialchars($row['admin_comments'] ?? '-'); ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="7" style="text-align: center; color: #64748b; padding: 30px;">No history found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- Action Modal -->
<div id="actionModal" class="modal">
    <div class="modal-content">
        <h3 id="modalTitle" style="margin-top: 0;">Process Claim</h3>
        <form method="POST">
            <input type="hidden" name="action" value="update_claim">
            <input type="hidden" name="claim_id" id="modalClaimId">
            <input type="hidden" name="status" id="modalStatus">
            
            <label style="font-size: 13px; color: #94a3b8;">Admin Comments (Optional)</label>
            <textarea name="comments" rows="3" placeholder="Add notes..."></textarea>
            
            <div style="display: flex; gap: 10px; margin-top: 15px;">
                <button type="button" onclick="document.getElementById('actionModal').style.display='none'" style="flex: 1; padding: 10px; background: #333; color: white; border: none; border-radius: 8px; cursor: pointer;">Cancel</button>
                <button type="submit" id="modalBtn" style="flex: 1; padding: 10px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; color: white;">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAction(id, status) {
        document.getElementById('actionModal').style.display = 'flex';
        document.getElementById('modalClaimId').value = id;
        document.getElementById('modalStatus').value = status;
        document.getElementById('modalTitle').innerText = status + ' Claim #' + id;
        
        const btn = document.getElementById('modalBtn');
        if(status === 'Approved') {
            btn.style.background = '#10b981';
            btn.innerText = 'Approve Claim';
        } else {
            btn.style.background = '#ef4444';
            btn.innerText = 'Reject Claim';
        }
    }
    
    // Close on outside click
    window.onclick = function(event) {
        if (event.target == document.getElementById('actionModal')) {
            document.getElementById('actionModal').style.display = 'none';
        }
    }
</script>

</body>
</html>
