<?php
session_start();
include 'includes/db_connect.php';

$search_query = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$dept_filter = isset($_GET['dept']) ? mysqli_real_escape_string($conn, $_GET['dept']) : '';

$is_logged_in = isset($_SESSION['user_id']);

// Base Query
$sql = "SELECT r.name, r.profile_photo, d.department, d.specialization, d.qualification, d.experience, d.bio, d.consultation_fee, u.user_id 
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
                    <button class="btn-book-now" onclick='openProfile(<?php echo htmlspecialchars(json_encode([
                        "id" => $row["user_id"],
                        "name" => $row["name"],
                        "dept" => $row["department"],
                        "spec" => $row["specialization"],
                        "qual" => $row["qualification"],
                        "exp" => $row["experience"],
                        "bio" => $row["bio"] ?? "No biography available.",
                        "fee" => $row["consultation_fee"] ?? "500",
                        "photo" => $row["profile_photo"]
                    ], JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>)' style="background: transparent; border: 1px solid #0c2d6a; color: #0c2d6a; margin-right: 10px;">View Profile</button>
                    
                    <?php 
                    $redirect_url = urlencode("appointment_form.php?doctor_id=" . $row['user_id']);
                    $book_link = $is_logged_in ? "appointment_form.php?doctor_id=" . $row['user_id'] : "login.php?redirect=" . $redirect_url;
                    ?>
                    <a href="<?php echo $book_link; ?>" class="btn-book-now">Book Now</a>
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

<!-- Profile Modal -->
<div id="profileModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; width: 90%; max-width: 600px; border-radius: 20px; padding: 30px; position: relative; animation: slideIn 0.3s ease-out;">
        <span onclick="closeProfile()" style="position: absolute; right: 20px; top: 20px; font-size: 24px; cursor: pointer; color: #999;">&times;</span>
        
        <div style="text-align: center; margin-bottom: 25px;">
            <div id="modalPhoto" style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; margin: 0 auto 15px; border: 4px solid #f0f0f0;">
                <img src="" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <h2 id="modalName" style="color: #0c2d6a; margin-bottom: 5px;"></h2>
            <p id="modalDept" style="color: #1e90ff; font-weight: 700; text-transform: uppercase; font-size: 13px;"></p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; text-align: center; background: #f8fafc; padding: 15px; border-radius: 12px;">
            <div>
                <small style="color: #64748b;">Qualification</small>
                <div id="modalQual" style="font-weight: 600; color: #334155;"></div>
            </div>
            <div>
                 <small style="color: #64748b;">Experience</small>
                <div id="modalExp" style="font-weight: 600; color: #334155;"></div>
            </div>
            <div>
                 <small style="color: #64748b;">Specialization</small>
                <div id="modalSpec" style="font-weight: 600; color: #334155;"></div>
            </div>
             <div>
                 <small style="color: #64748b;">Consultation Fee</small>
                <div id="modalFee" style="font-weight: 600; color: #10b981;"></div>
            </div>
        </div>

        <div style="margin-bottom: 25px;">
            <h4 style="color: #334155; margin-bottom: 10px;">About Doctor</h4>
            <p id="modalBio" style="color: #64748b; line-height: 1.6; font-size: 14px; background: #fff; padding: 15px; border: 1px solid #f1f5f9; border-radius: 10px;"></p>
        </div>

        <div style="text-align: center;">
            <a href="#" id="modalBookBtn" class="btn-book-now" style="width: 100%; display: block; text-align: center; padding: 15px;">Book Now</a>
        </div>
    </div>
</div>

<script>
    var isLoggedIn = <?php echo json_encode($is_logged_in); ?>;

    function openProfile(data) {
        document.getElementById('modalName').innerText = data.name;
        document.getElementById('modalDept').innerText = data.dept;
        document.getElementById('modalSpec').innerText = data.spec;
        document.getElementById('modalQual').innerText = data.qual;
        document.getElementById('modalExp').innerText = data.exp + " Years";
        document.getElementById('modalFee').innerText = "₹" + data.fee;
        document.getElementById('modalBio').innerText = data.bio;
        
        let photo = data.photo && data.photo != '' ? data.photo : 'assets/images/default_doctor.png';
        document.getElementById('modalPhoto').innerHTML = '<img src="' + photo + '" style="width: 100%; height: 100%; object-fit: cover;">';
        
        let btn = document.getElementById('modalBookBtn');
        if (btn) {
            if (isLoggedIn) {
                btn.href = "appointment_form.php?doctor_id=" + data.id;
            } else {
                let redirect = encodeURIComponent("appointment_form.php?doctor_id=" + data.id);
                btn.href = "login.php?redirect=" + redirect;
            }
        }

        document.getElementById('profileModal').style.display = 'flex';
    }

    function closeProfile() {
        document.getElementById('profileModal').style.display = 'none';
    }

    // Close on outside click
    window.onclick = function(event) {
        if (event.target == document.getElementById('profileModal')) {
            closeProfile();
        }
    }
</script><?php include 'includes/footer.php'; ?>
