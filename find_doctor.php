<?php
include 'includes/db_connect.php';

$search_query = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$dept_filter = isset($_GET['dept']) ? mysqli_real_escape_string($conn, $_GET['dept']) : '';

// Base Query
$sql = "SELECT r.name, r.profile_photo, d.department, d.specialization, d.qualification, d.experience, u.user_id 
        FROM registrations r 
        JOIN users u ON r.registration_id = u.registration_id 
        LEFT JOIN doctors d ON u.user_id = d.user_id 
        WHERE u.role = 'doctor'";

if (!empty($search_query)) {
    $sql .= " AND (r.name LIKE '%$search_query%' OR d.specialization LIKE '%$search_query%' OR d.department LIKE '%$search_query%')";
}

if (!empty($dept_filter)) {
    $sql .= " AND d.department = '$dept_filter'";
}

$sql .= " ORDER BY r.name ASC";
$result = $conn->query($sql);

// Fetch departments for filter
$depts_res = $conn->query("SELECT DISTINCT department FROM doctors WHERE department IS NOT NULL AND department != ''");
$departments = [];
while($d = $depts_res->fetch_assoc()) $departments[] = $d['department'];

include 'includes/header.php';
?>

<style>
    .find-doctor-hero {
        background: linear-gradient(rgba(10, 31, 68, 0.8), rgba(10, 31, 68, 0.8)), url('images/bgimg.jpg') center/cover;
        padding: 80px 0;
        text-align: center;
        color: white;
    }
    .search-box-container {
        max-width: 800px;
        margin: -40px auto 40px;
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        position: relative;
        z-index: 10;
    }
    .search-form {
        display: flex;
        gap: 15px;
    }
    .search-input {
        flex: 2;
        padding: 15px 20px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        font-family: 'Poppins', sans-serif;
        font-size: 15px;
        outline: none;
        transition: border-color 0.3s;
    }
    .search-input:focus {
        border-color: #1e90ff;
    }
    .search-select {
        flex: 1;
        padding: 15px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        font-family: 'Poppins', sans-serif;
        outline: none;
    }
    .btn-search {
        background: #1e90ff;
        color: white;
        border: none;
        padding: 0 30px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s;
    }
    .btn-search:hover {
        background: #0077e6;
    }
    .doctors-results-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 30px;
        margin-top: 40px;
    }
    .no-results {
        text-align: center;
        padding: 60px;
        color: #666;
    }
    .doctor-card-premium {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid #f0f0f0;
        transition: transform 0.3s, box-shadow 0.3s;
        text-align: center;
        padding: 30px;
    }
    .doctor-card-premium:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.08);
    }
    .doctor-thumb {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        margin: 0 auto 20px;
        overflow: hidden;
        background: #eef7ff;
        border: 4px solid #f8faff;
    }
    .doctor-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .result-dept {
        color: #1e90ff;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 5px;
    }
    .doctor-card-premium h3 {
        color: #0c2d6a;
        margin-bottom: 5px;
        font-size: 20px;
    }
    .result-spec {
        color: #666;
        font-size: 14px;
        margin-bottom: 15px;
    }
    .result-meta {
        font-size: 12px;
        color: #999;
        border-top: 1px solid #f0f0f0;
        padding-top: 15px;
        margin-top: 15px;
        display: flex;
        justify-content: center;
        gap: 20px;
    }
    .btn-book-now {
        display: inline-block;
        margin-top: 20px;
        padding: 10px 25px;
        background: #0c2d6a;
        color: white;
        text-decoration: none;
        border-radius: 25px;
        font-weight: 600;
        font-size: 14px;
        transition: background 0.3s;
    }
    .btn-book-now:hover {
        background: #1e90ff;
    }
</style>

<div class="find-doctor-hero">
    <div class="container text-center">
        <span class="welcome-text" style="color: #4fc3f7;">TEAM OF PROFESSIONALS</span>
        <h1 style="font-size: 48px; margin-top: 10px;">Find Your Specialist</h1>
        <p style="opacity: 0.8; max-width: 600px; margin: 15px auto;">Search from our pool of expert doctors across various departments.</p>
    </div>
</div>

<div class="container">
    <div class="search-box-container">
        <form action="find_doctor.php" method="GET" class="search-form">
            <input type="text" name="search" class="search-input" placeholder="Search by name or specialty..." value="<?php echo htmlspecialchars($search_query); ?>">
            <select name="dept" class="search-select">
                <option value="">All Departments</option>
                <?php foreach($departments as $d): ?>
                    <option value="<?php echo htmlspecialchars($d); ?>" <?php echo ($dept_filter == $d) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($d); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-search">SEARCH</button>
        </form>
    </div>

    <div class="doctors-results-grid">
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="doctor-card-premium">
                    <div class="doctor-thumb">
                        <?php if(!empty($row['profile_photo']) && file_exists($row['profile_photo'])): ?>
                            <img src="<?php echo $row['profile_photo']; ?>" alt="<?php echo $row['name']; ?>">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #eef7ff; color: #1e90ff; font-size: 60px;">
                                <i class="fas fa-user-md"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="result-dept"><?php echo htmlspecialchars($row['department'] ?? 'General Medicine'); ?></div>
                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                    <p class="result-spec"><?php echo htmlspecialchars($row['specialization'] ?? 'Medical Consultant'); ?></p>
                    <p style="font-size: 13px; color: #777;"><?php echo htmlspecialchars($row['qualification'] ?? 'MBBS'); ?></p>
                    
                    <div class="result-meta">
                        <span><i class="fas fa-user-clock"></i> <?php echo htmlspecialchars($row['experience'] ?? '5+'); ?> Exp</span>
                        <span><i class="fas fa-check-circle"></i> Verified</span>
                    </div>
                    <a href="login.php" class="btn-book-now">BOOK APPOINTMENT</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-results" style="grid-column: 1 / -1;">
                <i class="fas fa-search" style="font-size: 50px; color: #ddd; margin-bottom: 20px; display: block;"></i>
                <h2>No Doctors Found</h2>
                <p>Try adjusting your search criteria or explore all departments.</p>
                <a href="find_doctor.php" style="color: #1e90ff; font-weight: 600; margin-top: 15px; display: inline-block;">View All Doctors</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div style="margin-bottom: 100px;"></div>

<?php include 'includes/footer.php'; ?>
