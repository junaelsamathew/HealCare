<?php
class InsuranceHandler {
    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    // Get Active Policy for a Patient
    public function getActivePolicy($patient_id) {
        $sql = "SELECT * FROM insurance_policies WHERE patient_id = ? AND status = 'Active' AND valid_until >= CURDATE() LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return null;
        
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Get Total Covered Amount for a Policy (Used Limit)
    public function getUsedLimit($policy_id) {
        $sql = "SELECT SUM(covered_amount) as total_used FROM insurance_claims WHERE policy_id = ? AND status = 'Approved'";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return 0;
        
        $stmt->bind_param("i", $policy_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (float)($result['total_used'] ?? 0);
    }

    // Calculate Coverage
    public function calculateCoverage($amount, $policy) {
        if (!$policy) {
            return ['covered' => 0, 'payable' => $amount, 'remaining_limit' => 0];
        }

        $percentage = (int)$policy['coverage_percentage'];
        $total_limit = (float)$policy['coverage_limit'];
        $used_limit = $this->getUsedLimit($policy['policy_id']);
        $remaining_limit = max(0, $total_limit - $used_limit);

        // Calculate theoretical covered amount based on percentage
        $covered = ($amount * $percentage) / 100;

        // Cap at remaining limit
        if ($covered > $remaining_limit) {
            $covered = $remaining_limit;
        }

        $payable = $amount - $covered;

        return [
            'covered' => number_format($covered, 2, '.', ''), 
            'payable' => number_format($payable, 2, '.', ''),
            'remaining_limit' => number_format($remaining_limit, 2, '.', '')
        ];
    }

    // Create a Claim Record
    public function createClaim($bill_id, $policy_id, $patient_id, $total, $covered, $payable) {
        $sql = "INSERT INTO insurance_claims (bill_id, policy_id, patient_id, total_bill_amount, covered_amount, patient_payable, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("iiiddd", $bill_id, $policy_id, $patient_id, $total, $covered, $payable);
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        return false;
    }
}
?>
