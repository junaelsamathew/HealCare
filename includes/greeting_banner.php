<?php
/**
 * Personalized Greeting Banner Component
 * @param string $display_name The name to display in the greeting
 * @param string $message The message to display under the greeting
 * @param array $action Optional button details [text, link, icon, onclick]
 */
function renderGreetingBanner($display_name, $message, $action = null) {
    include_once 'greeting_logic.php';
    global $greeting;
    ?>
    <div class="greeting-banner" style="background: linear-gradient(135deg, #1e293b, #0f172a); padding: 35px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 30px; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
        <!-- Abstract Shapes for Premium Feel -->
        <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(59, 130, 246, 0.05); border-radius: 50%; filter: blur(40px);"></div>
        <div style="position: absolute; bottom: -30px; left: 10%; width: 150px; height: 150px; background: rgba(79, 195, 247, 0.03); border-radius: 50%; filter: blur(30px);"></div>
        
        <div style="position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="color: #fff; font-size: 28px; font-weight: 700; margin-bottom: 10px;">
                    <?php echo $greeting; ?>, <?php echo htmlspecialchars($display_name); ?>
                </h2>
                <p style="color: #94a3b8; font-size: 15px; max-width: 600px; line-height: 1.6;">
                    <?php echo $message; ?>
                </p>
                
                <?php if ($action): ?>
                    <button 
                        class="btn-create-appt" 
                        onclick="<?php echo $action['onclick'] ?? ''; ?>" 
                        style="margin-top: 20px; background: #3b82f6; color: white; border: none; padding: 12px 25px; border-radius: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 10px; transition: 0.3s;"
                        onmouseover="this.style.background='#2563eb'"
                        onmouseout="this.style.background='#3b82f6'"
                    >
                        <?php if (isset($action['icon'])): ?>
                            <i class="<?php echo $action['icon']; ?>"></i>
                        <?php endif; ?>
                        <?php echo $action['text']; ?>
                    </button>
                <?php endif; ?>
            </div>
            
            <div style="display: none; md: flex;">
                 <!-- Icon representative of time -->
                 <i class="fas <?php 
                    if ($greeting == 'Good Morning') echo 'fa-sun';
                    elseif ($greeting == 'Good Afternoon') echo 'fa-cloud-sun';
                    elseif ($greeting == 'Good Evening') echo 'fa-moon';
                    else echo 'fa-stars';
                 ?>" style="font-size: 80px; color: rgba(255,255,255,0.03);"></i>
            </div>
        </div>
    </div>
    <?php
}
?>
