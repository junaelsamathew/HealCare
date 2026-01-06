<?php
session_start();
include 'includes/db_connect.php';

// Mock Doctors Data (Consistent with Index.php)
$doctors = [
    ['id' => 1, 'name' => 'Dr. Abraham Mohan', 'dept' => 'General Surgery', 'exp' => '15 Years', 'img' => 'images/doctor-1.jpg', 'timings' => 'Mon-Sat 09:00 AM - 02:00 PM'],
    ['id' => 2, 'name' => 'Dr. Suresh Kumar', 'dept' => 'Orthopedics', 'exp' => '12 Years', 'img' => 'images/doctor-2.jpg', 'timings' => 'Mon-Fri 10:00 AM - 04:00 PM'],
    ['id' => 3, 'name' => 'Dr. Arjun Reddy', 'dept' => 'Cardiology', 'exp' => '10 Years', 'img' => 'images/doctor-3.jpg', 'timings' => 'Mon-Sat 11:00 AM - 05:00 PM'],
    ['id' => 4, 'name' => 'Dr. Lakshmi Devi', 'dept' => 'Ophthalmology', 'exp' => '8 Years', 'img' => 'images/doctor-4.jpg', 'timings' => 'Tue-Sun 09:00 AM - 01:00 PM'],
    ['id' => 5, 'name' => 'Dr. Vikram Singh', 'dept' => 'Dermatology', 'exp' => '9 Years', 'img' => 'images/doctor-5.jpg', 'timings' => 'Mon-Fri 02:00 PM - 08:00 PM'],
    ['id' => 6, 'name' => 'Dr. Rajesh Khanna', 'dept' => 'ENT', 'exp' => '14 Years', 'img' => 'images/doctor-6.jpg', 'timings' => 'Mon-Sat 08:00 AM - 12:00 PM'],
    ['id' => 7, 'name' => 'Dr. Meera Krishnan', 'dept' => 'Neurology', 'exp' => '11 Years', 'img' => 'images/doctor-7.jpg', 'timings' => 'Wed-Mon 10:00 AM - 06:00 PM'],
    ['id' => 8, 'name' => 'Dr. Akshay Kumar', 'dept' => 'Nephrology', 'exp' => '7 Years', 'img' => 'images/doctor-8.jpg', 'timings' => 'Mon-Fri 09:00 AM - 03:00 PM'],
    ['id' => 9, 'name' => 'Dr. Ananya Iyer', 'dept' => 'Pediatrics', 'exp' => '6 Years', 'img' => 'images/doctor-9.jpg', 'timings' => 'Mon-Sat 09:00 AM - 04:00 PM'],
    ['id' => 10, 'name' => 'Dr. Sneha Gupta', 'dept' => 'Gynecology', 'exp' => '13 Years', 'img' => 'images/doctor-10.jpg', 'timings' => 'Mon-Sat 10:00 AM - 02:00 PM']
];

// Handle Filtering
$department_filter = isset($_GET['dept']) ? $_GET['dept'] : '';
$filtered_doctors = $doctors;

if ($department_filter && $department_filter != 'All') {
    $filtered_doctors = array_filter($doctors, function($doc) use ($department_filter) {
        return $doc['dept'] === $department_filter;
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find a Doctor - HealCare</title>
    <link rel="stylesheet" href="styles/landing.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8fafc; }
        .page-header {
            background: #1e40af;
            color: white;
            padding: 80px 0 40px;
            text-align: center;
        }
        .page-header h1 { font-size: 2.5rem; margin-bottom: 15px; }
        .page-header p { font-size: 1.1rem; opacity: 0.9; }

        .filter-section {
            background: white;
            padding: 30px 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 50px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .filter-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
            padding: 0 20px;
        }
        .filter-btn {
            padding: 10px 25px;
            border: 1px solid #e2e8f0;
            border-radius: 50px;
            background: white;
            color: #64748b;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        .filter-btn:hover, .filter-btn.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }

        .doctors-grid {
            max-width: 1200px;
            margin: 0 auto 80px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            padding: 0 20px;
        }
        
        .doctor-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
        }
        .doctor-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .doc-img-wrapper {
            height: 250px;
            width: 100%;
            overflow: hidden;
            background: #f1f5f9;
        }
        .doc-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        .doctor-card:hover .doc-img-wrapper img {
            transform: scale(1.05);
        }
        .doc-info {
            padding: 25px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .doc-dept {
            color: #3b82f6;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .doc-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 10px;
        }
        .doc-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            color: #64748b;
            font-size: 0.9rem;
        }
        .doc-meta i { color: #94a3b8; margin-right: 5px; }
        
        .timings {
            background: #f0f9ff;
            color: #0369a1;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .btn-book-appt {
            display: block;
            width: 100%;
            padding: 12px;
            background: #1e40af;
            color: white;
            text-align: center;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s;
            margin-top: auto;
        }
        .btn-book-appt:hover {
            background: #1e3a8a;
        }

        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 50px;
            color: #94a3b8;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <header class="page-header">
        <h1>Find a Doctor</h1>
        <p>Expert care from the best specialists in the field.</p>
    </header>

    <div class="filter-section">
        <div class="filter-container" style="flex-direction: column; align-items: center; gap: 20px;">
            <div style="width: 100%; max-width: 600px; position: relative;">
                <input type="text" id="doctorSearch" placeholder="Search by Doctor Name or Department..." 
                       style="width: 100%; padding: 15px 50px 15px 25px; border-radius: 50px; border: 1px solid #e2e8f0; font-size: 1rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); outline: none;">
                <i class="fas fa-search" style="position: absolute; right: 25px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
            </div>
            
            <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
                <a href="?dept=All" class="filter-btn <?php echo (!$department_filter || $department_filter == 'All') ? 'active' : ''; ?>">All Specialists</a>
                <a href="?dept=Cardiology" class="filter-btn <?php echo ($department_filter == 'Cardiology') ? 'active' : ''; ?>">Cardiology</a>
                <a href="?dept=Neurology" class="filter-btn <?php echo ($department_filter == 'Neurology') ? 'active' : ''; ?>">Neurology</a>
                <a href="?dept=Orthopedics" class="filter-btn <?php echo ($department_filter == 'Orthopedics') ? 'active' : ''; ?>">Orthopedics</a>
                <a href="?dept=Gynecology" class="filter-btn <?php echo ($department_filter == 'Gynecology') ? 'active' : ''; ?>">Gynecology</a>
                <a href="?dept=Pediatrics" class="filter-btn <?php echo ($department_filter == 'Pediatrics') ? 'active' : ''; ?>">Pediatrics</a>
                <a href="?dept=Dermatology" class="filter-btn <?php echo ($department_filter == 'Dermatology') ? 'active' : ''; ?>">Dermatology</a>
                <a href="?dept=ENT" class="filter-btn <?php echo ($department_filter == 'ENT') ? 'active' : ''; ?>">ENT</a>
            </div>
        </div>
    </div>

    <div class="doctors-grid">
        <?php if(empty($filtered_doctors)): ?>
            <div class="no-results">
                <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 20px; display: block;"></i>
                No doctors found for this department.
            </div>
        <?php else: ?>
            <?php foreach($filtered_doctors as $doc): ?>
            <div class="doctor-card">
                <div class="doc-img-wrapper">
                    <img src="<?php echo $doc['img']; ?>" alt="<?php echo $doc['name']; ?>" onerror="this.src='images/doctor-1.jpg'">
                </div>
                <div class="doc-info">
                    <div class="doc-dept"><?php echo $doc['dept']; ?></div>
                    <h3 class="doc-name"><?php echo $doc['name']; ?></h3>
                    <div class="doc-meta">
                        <span><i class="fas fa-briefcase-medical"></i> <?php echo $doc['exp']; ?> Exp</span>
                    </div>
                    <div class="timings">
                        <i class="far fa-clock"></i> <?php echo $doc['timings']; ?>
                    </div>
                    <a href="book_appointment.php?doctor_id=<?php echo $doc['id']; ?>&doctor_name=<?php echo urlencode($doc['name']); ?>&dept=<?php echo urlencode($doc['dept']); ?>" class="btn-book-appt">
                        Book Appointment
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        document.getElementById('doctorSearch').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let cards = document.querySelectorAll('.doctor-card');
            let hasResult = false;

            cards.forEach(function(card) {
                let name = card.querySelector('.doc-name').textContent.toLowerCase();
                let dept = card.querySelector('.doc-dept').textContent.toLowerCase();
                
                if (name.includes(filter) || dept.includes(filter)) {
                    card.style.display = 'flex';
                    hasResult = true;
                } else {
                    card.style.display = 'none';
                }
            });

            // Handle no results message visibility
            let noRes = document.querySelector('.no-results');
            if(noRes) {
                if(!hasResult && filter.length > 0) {
                    noRes.style.display = 'block';
                    noRes.innerHTML = '<i class="fas fa-search" style="font-size: 3rem; margin-bottom: 20px; display: block;"></i> No doctors found matching "' + this.value + '"';
                } else if (!hasResult && filter.length === 0) {
                     // If original list was empty from PHP, show default message
                     noRes.style.display = 'block';
                } else {
                    noRes.style.display = 'none';
                }
            }
        });
    </script>

</body>
</html>
