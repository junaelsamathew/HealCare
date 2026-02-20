                $doctors_sql = "SELECT d.*, r.name, u.username, u.email,
                                (SELECT COUNT(*) FROM doctor_leaves dl 
                                 WHERE dl.doctor_id = d.user_id 
                                 AND dl.status = 'Approved' 
                                 AND CURDATE() BETWEEN dl.start_date AND dl.end_date) as is_on_leave
                                FROM doctors d 
                                JOIN users u ON d.user_id = u.user_id 
                                JOIN registrations r ON u.registration_id = r.registration_id 
                                ORDER BY d.department ASC";
                $doctors_res = $conn->query($doctors_sql);
                
                if ($doctors_res && $doctors_res->num_rows > 0):
                    $current_dept = '';
                    while($doc = $doctors_res->fetch_assoc()):
                        // Override availability status if on leave
                        $status = $doc['availability_status'] ?: 'Available';
                        if ($doc['is_on_leave'] > 0) {
                            $status = 'On Leave';
                        }

                        if ($current_dept != $doc['department']):
                            $current_dept = $doc['department'];
                            echo '<div style="background: rgba(59, 130, 246, 0.05); padding: 10px 20px; border-radius: 8px; margin: 30px 0 15px; border-left: 4px solid var(--primary-blue);">';
                            echo '<h4 style="color: var(--primary-blue); font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">' . htmlspecialchars($current_dept ?: 'Unassigned Dept') . '</h4>';
                            echo '</div>';
                        endif;
                ?>
                        <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; transition: all 0.3s; hover: background: rgba(255,255,255,0.04);">
                            <div style="display: flex; align-items: center; gap: 20px;">
                                <div style="width: 50px; height: 50px; background: var(--primary-blue); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700;">
                                    <?php echo substr($doc['name'], 0, 1); ?>
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-size: 16px;">DR. <?php echo htmlspecialchars($doc['name']); ?></h4>
                                    <p style="font-size: 12px; color: var(--text-gray);"><?php echo htmlspecialchars($doc['specialization']); ?> • <?php echo htmlspecialchars($doc['username']); ?></p>
                                    <span class="badge badge-<?php 
                                        echo ($status == 'Available' ? 'active' : ($status == 'Busy' ? 'pending' : 'rejected')); 
                                    ?>" style="margin-top: 5px; display: inline-block;">
                                        <?php echo $status; ?>
                                    </span>
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <button onclick="openScheduleModal(<?php echo $doc['user_id']; ?>, '<?php echo htmlspecialchars($doc['name']); ?>')" class="btn btn-primary" style="font-size: 12px; padding: 8px 15px;"><i class="fas fa-calendar-alt"></i> Schedule</button>
                                <button onclick="openDeptModal(<?php echo $doc['user_id']; ?>, '<?php echo htmlspecialchars($doc['department']); ?>', '<?php echo htmlspecialchars($doc['specialization']); ?>')" class="btn btn-warning" style="font-size: 12px; padding: 8px 15px;"><i class="fas fa-building"></i> Dept</button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="update_doctor_availability">
                                    <input type="hidden" name="doctor_id" value="<?php echo $doc['user_id']; ?>">
                                    <select name="availability_status" onchange="this.form.submit()" style="padding: 6px 10px; font-size: 12px; border-radius: 6px; background: var(--darkest-blue); color: white; border: 1px solid var(--border-color);">
                                        <option value="Available" <?php echo $status == 'Available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="Busy" <?php echo $status == 'Busy' ? 'selected' : ''; ?>>Busy</option>
                                        <option value="On Leave" <?php echo $status == 'On Leave' ? 'selected' : ''; ?>>On Leave</option>
                                    </select>
                                </form>
                            </div>
                        </div>
                <?php endwhile; else: ?>
                    <div class="placeholder-section">
                        <i class="fas fa-user-md"></i>
                        <h3>No Doctors Found</h3>
                        <p>Start by adding doctors from the "Create User" section.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Schedule Modal -->
            <div id="scheduleModal" class="modal">
                <div class="modal-content" style="width: 600px;">
                    <span class="close-modal" onclick="closeModal('scheduleModal')">&times;</span>
                    <h3 id="scheduleTitle" style="margin-bottom: 25px;">Manage Schedule</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_doctor_schedule">
                        <input type="hidden" name="doctor_id" id="sched_doc_id">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Day of Week</label>
                                <select name="day_of_week" required>
                                    <option value="Monday">Monday</option>
                                    <option value="Tuesday">Tuesday</option>
