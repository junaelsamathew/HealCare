<?php
include 'includes/db_connect.php';
include 'includes/header.php';

$email = $_GET['email'] ?? '';

if (empty($email)) {
    echo "<div class='container' style='padding: 100px 0; text-align: center;'><h2>Doctor not found</h2><a href='index.php'>Go Back</a></div>";
    include 'includes/footer.php';
    exit();
}

$stmt = $conn->prepare("SELECT r.*, u.user_id, d.specialization, d.qualification, d.experience, d.department, d.designation, d.consultation_fee, d.bio 
                        FROM registrations r 
                        JOIN users u ON r.registration_id = u.registration_id 
                        LEFT JOIN doctors d ON u.user_id = d.user_id 
                        WHERE r.email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    echo "<div class='container' style='padding: 100px 0; text-align: center;'><h2>Doctor not found</h2><a href='index.php'>Go Back</a></div>";
    include 'includes/footer.php';
    exit();
}

$doc = $res->fetch_assoc();

$name = $doc['name'];
if (stripos($name, 'Dr.') === false) {
    $name = 'Dr. ' . $name;
}

$dept = $doc['specialization'] ?? $doc['department'] ?? 'General Medicine';
$qual = $doc['qualification'] ?? $doc['highest_qualification'] ?? 'MBBS';
$certs = $doc['certifications'] ?? 'N/A';
$languages = $doc['languages_known'] ?? 'English, Hindi, Malayalam';
$about = $doc['bio'] ?? $doc['additional_details'] ?? 'Expert medical consultation with years of experience in providing quality healthcare services.';

?>

<style>
    .doctor-profile-page {
        padding: 80px 0;
        background: #fff;
        font-family: 'Poppins', sans-serif;
        position: relative;
    }
    .profile-container {
        max-width: 1100px;
        margin: 0 auto;
        padding: 0 50px;
    }
    .profile-header {
        margin-bottom: 40px;
    }
    .profile-header h1 {
        color: #1e293b;
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 10px;
        letter-spacing: -0.5px;
    }
    .profile-specialty {
        color: #0088cc;
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 40px;
    }
    .profile-info-section {
        margin-bottom: 40px;
    }
    .profile-info-label {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 15px;
        display: block;
    }
    .profile-info-value {
        font-size: 18px;
        color: #334155;
        line-height: 1.6;
        margin-top: 15px;
    }
    
    /* Accordion Styles */
    .accordion-container {
        margin-top: 50px;
        border-top: 1px solid #f1f5f9;
        max-width: 900px;
    }
    .accordion-item {
        border-bottom: 1px solid #f1f5f9;
    }
    .accordion-header {
        padding: 22px 5px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        background: #fff;
        transition: all 0.2s ease;
    }
    .accordion-header:hover {
        background: #fafafa;
    }
    .accordion-header.active {
        background: #f0f7ff;
        margin: 0 -10px;
        padding: 22px 15px;
        border: 1px solid #c2e0ff;
        border-radius: 8px;
    }
    .accordion-header h4 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #0077b3;
    }
    .accordion-icon {
        font-size: 16px;
        color: #0077b3;
        transition: transform 0.3s ease;
    }
    .accordion-header.active .accordion-icon {
        transform: rotate(0deg); /* We'll change icon class instead */
    }
    .accordion-content {
        padding: 0;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease, padding 0.3s ease;
    }
    .accordion-content.open {
        padding: 20px 5px;
        max-height: 500px;
    }
    .accordion-content p {
        margin: 0;
        color: #475569;
        line-height: 1.8;
        font-size: 15px;
    }
    
    .sidebar-blue-indicator {
        position: absolute;
        left: 0;
        top: 350px;
        width: 45px;
        height: 65px;
        background: #00b0ff;
        border-top-right-radius: 4px;
        border-bottom-right-radius: 4px;
        z-index: 1;
    }

    .profile-flex-layout {
        display: flex;
        gap: 60px;
        align-items: flex-start;
        position: relative;
    }

    .profile-left-col {
        flex: 0 0 300px;
        text-align: center;
    }

    .profile-right-col {
        flex: 1;
    }

    .doctor-image-container {
        width: 280px;
        height: 280px;
        background: #e1f5fe;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .doctor-image-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .btn-book-appointment {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 280px;
        background: #00b0ff;
        color: white;
        text-decoration: none;
        padding: 18px 20px;
        border-radius: 4px;
        font-weight: 700;
        font-size: 15px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: background 0.3s ease;
    }

    .btn-book-appointment:hover {
        background: #0091ea;
    }

    @media (max-width: 900px) {
        .profile-flex-layout {
            flex-direction: column;
            gap: 40px;
        }
        .profile-left-col {
            flex: none;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
    }
</style>

<div class="doctor-profile-page">
    <div class="sidebar-blue-indicator"></div>
    <div class="profile-container">
        <div class="profile-flex-layout">
            <!-- Left Column: Image & Booking -->
            <div class="profile-left-col">
                <div class="doctor-image-container">
                    <?php 
                        $photo = $doc['profile_photo'];
                        if (empty($photo) || !file_exists($photo)) {
                            $photo = 'images/default-doctor.jpg';
                        }
                    ?>
                    <img src="<?php echo $photo; ?>" alt="<?php echo htmlspecialchars($name); ?>">
                </div>
                <?php 
                    $redirect_url = urlencode("appointment_form.php?doctor_id=" . $doc['user_id']);
                    $is_logged_in = isset($_SESSION['user_id']);
                    $book_link = $is_logged_in ? "appointment_form.php?doctor_id=" . $doc['user_id'] : "login.php?redirect=" . $redirect_url;
                ?>
                <a href="<?php echo $book_link; ?>" class="btn-book-appointment">BOOK AN APPOINTMENT</a>
            </div>

            <!-- Right Column: Info & Accordions -->
            <div class="profile-right-col">
                <div class="profile-header">
                    <h1><?php echo htmlspecialchars($name); ?></h1>
                    <div class="profile-specialty"><?php echo htmlspecialchars($dept); ?></div>
                </div>

                <div class="profile-info-section">
                    <span class="profile-info-label">Qualification:</span>
                    <div class="profile-info-value"><?php echo htmlspecialchars($qual); ?></div>
                </div>

        <div class="accordion-container">
            <!-- Fellowship and Certification -->
            <div class="accordion-item">
                <div class="accordion-header" id="accordion-fellowship">
                    <h4>Fellowship and Certification</h4>
                    <span class="accordion-icon"><i class="fas fa-chevron-down"></i></span>
                </div>
                <div class="accordion-content" id="content-fellowship">
                    <p><?php echo nl2br(htmlspecialchars($certs)); ?></p>
                </div>
            </div>

            <!-- About More -->
            <div class="accordion-item">
                <div class="accordion-header" id="accordion-about">
                    <h4>About More</h4>
                    <span class="accordion-icon"><i class="fas fa-chevron-right"></i></span>
                </div>
                <div class="accordion-content" id="content-about">
                    <p><?php echo nl2br(htmlspecialchars($about)); ?></p>
                </div>
            </div>

            <!-- Languages Known -->
            <div class="accordion-item">
                <div class="accordion-header" id="accordion-languages">
                    <h4>Languages Known</h4>
                    <span class="accordion-icon"><i class="fas fa-chevron-right"></i></span>
                </div>
                <div class="accordion-content" id="content-languages">
                    <p><?php echo htmlspecialchars($languages); ?></p>
                </div>
                </div> <!-- End Languages Item -->
            </div> <!-- End Accordion Container -->
        </div> <!-- End Right Col -->
    </div> <!-- End Flex Layout -->
</div> <!-- End Container -->
</div> <!-- End Page -->

<script>
    document.querySelectorAll('.accordion-header').forEach(header => {
        header.addEventListener('click', () => {
            const content = document.getElementById('content-' + header.id.split('-')[1]);
            const icon = header.querySelector('i');
            
            // Toggle active class on header
            header.classList.toggle('active');
            
            // Toggle open class on content
            content.classList.toggle('open');
            
            // Toggle icons (chevron down/right/up)
            if (content.classList.contains('open')) {
                icon.classList.remove('fa-chevron-right', 'fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                icon.classList.remove('fa-chevron-up');
                if (header.id === 'accordion-fellowship') {
                    icon.classList.add('fa-chevron-down');
                } else {
                    icon.classList.add('fa-chevron-right');
                }
            }
        });
    });
    
    // Default open first one
    document.getElementById('accordion-fellowship').click();
</script>

<?php include 'includes/footer.php'; ?>
