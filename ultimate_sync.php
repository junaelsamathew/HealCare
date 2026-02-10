<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'healcare');
if ($conn->connect_error) die("DB Error: " . $conn->connect_error);

// 1. Ensure column exists
$conn->query("ALTER TABLE ambulance_contacts ADD COLUMN IF NOT EXISTS image_url VARCHAR(255)");

// 2. Clear old data
$conn->query("TRUNCATE TABLE ambulance_contacts");

// 3. Insert fresh branded units
$sql = "INSERT INTO ambulance_contacts (driver_name, phone_number, vehicle_number, vehicle_type, location, availability, image_url) VALUES 
('HealCare Hospital - Unit 01', '+91 98765 43210', 'MH-12-HE-0001', 'Advanced Life Support', 'Main Campus', 'Available', 'https://images.unsplash.com/photo-1542884748-2b87b36c6b90?auto=format&fit=crop&q=80&w=1200'),
('HealCare Hospital - Unit 02', '+91 98765 43211', 'MH-12-HE-0002', 'Basic Life Support', 'South Wing', 'On Duty', 'https://images.unsplash.com/photo-1587745416684-47953f16f02f?auto=format&fit=crop&q=80&w=1200'),
('HealCare Hospital - Unit 03', '+91 98765 43212', 'MH-12-HE-0003', 'Cardiac Care Unit', 'Emergency Bay', 'Available', 'https://images.unsplash.com/photo-1626285861696-9f0bf5a49c6d?auto=format&fit=crop&q=80&w=1200')";

if ($conn->query($sql)) {
    echo "ULTIMATE_SYNC_SUCCESS: 3 Branded Units Created.";
} else {
    echo "Error: " . $conn->error;
}
?>
