<?php 
include 'includes/db_connect.php';
include 'includes/header.php'; 
?>

<div class="page-header" style="background-color: #f8f9fa; padding: 40px 0; text-align: center;">
    <div class="container">
        <h1>Emergency Department</h1>
        <p>24/7 Critical Care & Trauma Services</p>
    </div>
</div>

<section style="padding: 60px 0;">
    <div class="container">
        <div style="max-width: 800px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border-top: 5px solid #ff4444;">
            <div style="text-align: center; margin-bottom: 30px;">
                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#ff4444" stroke-width="1.5">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            
            <h2 style="color: #0a1f44; margin-bottom: 20px; text-align: center;">Emergency & Trauma Care</h2>
            <p style="color: #555; line-height: 1.8; margin-bottom: 20px;">
                Our Emergency Department is geared to meet all medical and surgical emergencies 24/7. We are equipped with advanced life support systems and a team of highly skilled emergency medicine specialists to handle critical situations with speed and precision.
            </p>
            
            <h4 style="color: #0a1f44; margin-top: 30px; margin-bottom: 15px;">Key Features:</h4>
            <ul class="check-list" style="list-style: none; padding: 0;">
                <li style="margin-bottom: 10px; padding-left: 25px; position: relative;"><i class="fas fa-check" style="position: absolute; left: 0; top: 5px; color: #ff4444;"></i> 24x7 Ambulance Service (+91 8086611101)</li>
                <li style="margin-bottom: 10px; padding-left: 25px; position: relative;"><i class="fas fa-check" style="position: absolute; left: 0; top: 5px; color: #ff4444;"></i> Trauma Care</li>
                <li style="margin-bottom: 10px; padding-left: 25px; position: relative;"><i class="fas fa-check" style="position: absolute; left: 0; top: 5px; color: #ff4444;"></i> Cardiac Emergencies</li>
                <li style="margin-bottom: 10px; padding-left: 25px; position: relative;"><i class="fas fa-check" style="position: absolute; left: 0; top: 5px; color: #ff4444;"></i> Pediatric Emergencies</li>
                <li style="margin-bottom: 10px; padding-left: 25px; position: relative;"><i class="fas fa-check" style="position: absolute; left: 0; top: 5px; color: #ff4444;"></i> Stroke Management</li>
                <li style="margin-bottom: 10px; padding-left: 25px; position: relative;"><i class="fas fa-check" style="position: absolute; left: 0; top: 5px; color: #ff4444;"></i> Poisoning & Snake Bite Care</li>
            </ul>

            <div style="margin-top: 40px; background-color: #ffe6e6; padding: 30px; border-radius: 15px;">
                <h3 style="color: #cc0000; margin-bottom: 25px; text-align: center;"><i class="fas fa-ambulance"></i> EMERGENCY AMBULANCE DIRECTORY</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <?php
                    $ambulances = $conn->query("SELECT * FROM ambulance_contacts WHERE availability = 'Available' ORDER BY created_at DESC");
                    if ($ambulances && $ambulances->num_rows > 0):
                        while ($amb = $ambulances->fetch_assoc()):
                    ?>
                        <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(255,0,0,0.05); border-left: 4px solid #ff4444;">
                            <h5 style="margin: 0; color: #0a1f44;"><?php echo htmlspecialchars($amb['driver_name']); ?></h5>
                            <p style="font-size: 11px; color: #777; margin-bottom: 10px;"><?php echo htmlspecialchars($amb['vehicle_type']); ?> • <?php echo htmlspecialchars($amb['vehicle_number']); ?></p>
                            <p style="font-size: 18px; font-weight: bold; color: #ff4444; margin: 0;">
                                <i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($amb['phone_number']); ?>
                            </p>
                            <small style="display: block; margin-top: 5px; color: #999;"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($amb['location']); ?></small>
                        </div>
                    <?php endwhile; else: ?>
                        <div style="grid-column: 1/-1; text-align: center; padding: 20px;">
                            <p style="font-size: 14px; font-weight: bold; color: #cc0000; margin: 0;">HOTLINE: (+254) 717 783 146</p>
                            <small style="color: #777;">No individual ambulance contacts currently listed.</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section><?php include 'includes/footer.php'; ?>
