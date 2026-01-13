<?php
require_once 'includes/db_connect.php';

// disable foreign key checks to allow truncate
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->query("TRUNCATE TABLE pharmacy_stock");
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "Cleared existing inventory.<br>";

$medicines = [
    // ANTIBIOTICS
    ['Amoxicillin 500mg', 'Capsule', 'Sun Pharma', 'AMX-2024-001', '2027-05-15', 500, 50, 5.00, 'Shelf A1'],
    ['Azithromycin 500mg', 'Tablet', 'Cipla', 'AZI-2024-088', '2026-12-01', 300, 30, 15.00, 'Shelf A2'],
    ['Ciprofloxacin 500mg', 'Tablet', 'Dr. Reddy', 'CIP-2024-102', '2027-01-20', 250, 40, 8.50, 'Shelf A2'],
    ['Cefixime 200mg', 'Tablet', 'Lupin', 'CFX-2024-456', '2026-08-30', 200, 25, 12.00, 'Shelf A3'],
    ['Augmentin 625mg', 'Tablet', 'GSK', 'AUG-2024-777', '2026-11-15', 150, 20, 25.00, 'Shelf A3'],
    
    // ANALGESICS / PAIN & FEVER
    ['Paracetamol 500mg', 'Tablet', 'GSK', 'PCM-2024-990', '2028-01-01', 2000, 100, 2.00, 'Shelf B1'],
    ['Paracetamol 650mg (Dolo)', 'Tablet', 'Micro Labs', 'DLO-2024-555', '2027-06-20', 1500, 100, 3.00, 'Shelf B1'],
    ['Ibuprofen 400mg', 'Tablet', 'Abbott', 'IBU-2024-123', '2027-03-10', 800, 50, 4.00, 'Shelf B2'],
    ['Diclofenac Sodium', 'Injection', 'Novartis', 'DIC-2024-INJ', '2026-05-05', 300, 20, 15.00, 'Minifridge 1'],
    ['Tramadol 50mg', 'Capsule', 'Mankind', 'TRM-2024-099', '2026-09-12', 400, 30, 18.00, 'Locker 1'],
    ['Aspirin 75mg', 'Tablet', 'Bayer', 'ASP-2024-001', '2027-02-28', 600, 50, 1.50, 'Shelf B3'],

    // GASTROINTESTINAL
    ['Omeprazole 20mg', 'Capsule', 'Dr. Reddy', 'OME-2024-333', '2027-07-07', 600, 50, 6.00, 'Shelf C1'],
    ['Pantoprazole 40mg', 'Tablet', 'Sun Pharma', 'PAN-2024-444', '2027-08-15', 800, 60, 9.00, 'Shelf C1'],
    ['Ranitidine 150mg', 'Tablet', 'Cadila', 'RAN-2024-111', '2026-04-20', 500, 40, 3.00, 'Shelf C2'],
    ['Ondansetron 4mg', 'Tablet', 'Cipla', 'OND-2024-222', '2027-01-01', 400, 30, 7.00, 'Shelf C2'],
    ['Digene Gel', 'Syrup', 'Abbott', 'DIG-2024-SYR', '2026-03-30', 100, 10, 120.00, 'Shelf C3'],

    // CHRONIC CARE (Diabetes, BP, Heart)
    ['Metformin 500mg', 'Tablet', 'USV', 'MET-2024-500', '2027-12-31', 1000, 100, 3.50, 'Shelf D1'],
    ['Glimepiride 1mg', 'Tablet', 'Sanofi', 'GLI-2024-001', '2027-05-10', 500, 50, 5.50, 'Shelf D1'],
    ['Amlodipine 5mg', 'Tablet', 'Pfizer', 'AML-2024-005', '2028-02-14', 800, 60, 4.50, 'Shelf D2'],
    ['Telmisartan 40mg', 'Tablet', 'Glenmark', 'TEL-2024-040', '2027-11-20', 600, 50, 8.00, 'Shelf D2'],
    ['Atorvastatin 10mg', 'Tablet', 'Sun Pharma', 'ATR-2024-010', '2027-06-30', 500, 40, 12.00, 'Shelf D3'],
    
    // RESPIRATORY / COLD / ALLERGY
    ['Cetirizine 10mg', 'Tablet', 'GSK', 'CET-2024-010', '2027-09-09', 1200, 100, 4.00, 'Shelf E1'],
    ['Levocetirizine 5mg', 'Tablet', 'Cipla', 'LEV-2024-005', '2027-10-10', 800, 80, 5.00, 'Shelf E1'],
    ['Cough Syrup (Dextromethorphan)', 'Syrup', 'Benadryl', 'CSY-2024-100', '2026-02-28', 150, 20, 110.00, 'Shelf E2'],
    ['Salbutamol Inhaler', 'Inhaler', 'Cipla', 'INH-2024-SAL', '2026-08-15', 50, 10, 250.00, 'Shelf E3'],
    ['Montelukast 10mg', 'Tablet', 'Ranbaxy', 'MON-2024-010', '2027-04-04', 300, 30, 14.00, 'Shelf E2'],

    // VITAMINS & SUPPLEMENTS
    ['Vitamin C 500mg', 'Tablet', 'Abbott', 'VIT-2024-CCC', '2026-07-01', 500, 50, 3.00, 'Shelf F1'],
    ['Calcium + Vit D3', 'Tablet', 'Shelcal', 'CAL-2024-500', '2027-01-15', 600, 60, 8.00, 'Shelf F1'],
    ['B-Complex (Neurobion)', 'Injection', 'Merck', 'NEU-2024-INJ', '2026-05-20', 200, 20, 18.00, 'Minifridge 1'],
    ['Multivitamin Syrup', 'Syrup', 'Zincovis', 'ZIN-2024-SYR', '2026-09-30', 100, 15, 145.00, 'Shelf F2'],
    ['Iron Folic Acid', 'Tablet', 'Orofer', 'IRO-2024-XT', '2027-03-10', 400, 40, 10.00, 'Shelf F2'],

    // INJECTIONS & EMERGENCY
    ['Furosemide (Lasix)', 'Injection', 'Sanofi', 'LAS-2024-INJ', '2026-11-01', 50, 10, 12.00, 'ER Cabinet'],
    ['Avil (Pheniramine)', 'Injection', 'Sanofi', 'AVI-2024-INJ', '2026-12-12', 100, 20, 8.00, 'ER Cabinet'],
    ['Dexamethasone', 'Injection', 'Zydus', 'DEX-2024-INJ', '2026-10-30', 80, 15, 10.00, 'ER Cabinet'],
    ['Tetanus Toxoid', 'Injection', 'Serum Inst', 'TT-2024-INJ', '2026-06-01', 200, 30, 25.00, 'Minifridge 2'],
    ['Insulin (Human Mixtard)', 'Injection', 'Novo Nordisk', 'INS-2024-MIX', '2025-12-31', 40, 10, 450.00, 'Fridge 1'],

    // TOPICAL / FIRST AID
    ['Silver Nitrate Gel', 'Cream', 'Virchow', 'SLV-2024-GEL', '2026-08-08', 50, 10, 85.00, 'Shelf G1'],
    ['Betadine Ointment', 'Cream', 'Win-Medicare', 'BET-2024-OIN', '2027-02-02', 100, 20, 65.00, 'Shelf G1'],
    ['Ciplox Eye Drops', 'Drops', 'Cipla', 'CIP-2024-EYE', '2026-01-20', 100, 15, 20.00, 'Shelf G2'],
    ['Otrivin Nasal Drops', 'Drops', 'GSK', 'OTR-2024-NAS', '2026-04-15', 80, 15, 55.00, 'Shelf G2']
];

$stmt = $conn->prepare("INSERT INTO pharmacy_stock (medicine_name, medicine_type, manufacturer, batch_number, expiry_date, quantity, minimum_stock, unit_price, location, last_restocked_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())");

foreach ($medicines as $med) {
    // $med is array: [Name, Type, Manufacturer, Batch, Expiry, Qty, MinStock, Price, Location]
    $stmt->bind_param("sssssiids", 
        $med[0], $med[1], $med[2], $med[3], $med[4], $med[5], $med[6], $med[7], $med[8]
    );
    
    if($stmt->execute()) {
        echo "Added: " . $med[0] . "<br>";
    } else {
        echo "Error adding " . $med[0] . ": " . $stmt->error . "<br>";
    }
}

echo "<h1>Pharmacy Inventory Populated Successfully!</h1>";
?>
