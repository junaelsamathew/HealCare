import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import UnexpectedAlertPresentException, TimeoutException

# CONFIGURATION
BASE_URL = "http://localhost/HealCare-main" 
DOCTOR_EMAIL = "mary.mariam@healcare.com"
DOCTOR_PASS = "Zoom#2023" 

# DELAY CONFIGURATION (in seconds)
STEP_DELAY = 2 

def run_consultation_automation():
    print(">>> Starting HealCare Doctor Consultation Automation (Improved Version)")
    
    # Initialize WebDriver
    options = webdriver.ChromeOptions()
    options.add_argument("--start-maximized")
    # Uncomment next line to run headless if needed
    # options.add_argument("--headless")
    driver = webdriver.Chrome(options=options)
    wait = WebDriverWait(driver, 15)

    try:
        # 1. LOGIN AS DOCTOR
        print("[1/5] Navigating to Login Page...")
        driver.get(f"{BASE_URL}/login.php")
        time.sleep(STEP_DELAY)

        print(f"      Entering Email: {DOCTOR_EMAIL}")
        email_input = wait.until(EC.visibility_of_element_located((By.NAME, "identity")))
        email_input.clear()
        email_input.send_keys(DOCTOR_EMAIL)
        
        print("      Entering Password...")
        pass_input = driver.find_element(By.NAME, "password")
        pass_input.clear()
        pass_input.send_keys(DOCTOR_PASS)
        time.sleep(1)

        print("      Clicking Login...")
        login_btn = driver.find_element(By.CLASS_NAME, "btn-login")
        login_btn.click()

        # Handle potential login failure alerts
        try:
            alert = driver.switch_to.alert
            alert_text = alert.text
            print(f"      ALERT DETECTED: {alert_text}")
            alert.accept()
            raise Exception(f"Login Failed with Alert: {alert_text}")
        except UnexpectedAlertPresentException as e:
            msg = e.alert_text if e.alert_text else "Unknown Alert"
            print(f"      Unexpected Alert: {msg}")
            raise Exception(f"Login Blocked by Alert: {msg}")
        except:
            # No alert, proceed
            pass

        # 2. DOCTOR DASHBOARD
        print("[2/5] Waiting for Doctor Dashboard...")
        try:
            wait.until(EC.url_contains("doctor_dashboard.php"))
            print("      Login Successful! Dashboard Accessed.")
        except TimeoutException:
            print("      TIMEOUT: Did not reach doctor_dashboard.php")
            print(f"      Current URL: {driver.current_url}")
            # Check if we are still on login page with error
            raise Exception("Login redirection failed or timed out.")

        time.sleep(STEP_DELAY)

        # 3. FIND AND START CONSULTATION
        print("[3/5] Locating patient in consultation queue...")
        
        try:
            # Look for existing 'Consult Now' (already accepted/scheduled)
            consult_btn = wait.until(EC.element_to_be_clickable((By.XPATH, "//a[contains(text(), 'Consult Now')]")))
            print(f"      Found scheduled patient. Starting consultation...")
            consult_btn.click()
        except:
            print("      'Consult Now' not found. Checking for 'Accept' buttons...")
            try:
                # Accept a 'Requested' appointment first
                accept_btn = wait.until(EC.element_to_be_clickable((By.XPATH, "//button[contains(text(), 'Accept')]")))
                print("      Accepting appointment...")
                accept_btn.click()
                time.sleep(STEP_DELAY)
                
                # After accepting, we need to click 'Consult Now' which should appear
                print("      Clicking 'Consult Now' for accepted patient...")
                consult_btn = wait.until(EC.element_to_be_clickable((By.XPATH, "//a[contains(text(), 'Consult Now')]")))
                consult_btn.click()
            except:
                # Last resort: check if modal is ALREADY open (sometimes happens due to auto-redirect)
                if "patient_id" in driver.current_url and "appt_id" in driver.current_url:
                    print("      Consultation modal appears already active via URL parameters.")
                else:
                    raise Exception("No appointments available for consultation in the current queue.")
        
        time.sleep(STEP_DELAY)

        # 4. FILL CONSULTATION FORM (Inside Modal)
        print("[4/5] Filling Consultation Data...")
        
        # Diagnosis
        print("      Entering Diagnosis...")
        diag_input = wait.until(EC.visibility_of_element_located((By.ID, "live_diagnosis")))
        diag_input.clear()
        diag_input.send_keys("Upper Respiratory Track Infection (Common Cold)")
        time.sleep(1)

        # Treatment
        print("      Entering Treatment Plan...")
        treat_input = driver.find_element(By.ID, "live_treatment")
        treat_input.clear()
        treat_input.send_keys("Advised bed rest. Increase fluid intake. Normal diet. Follow up in 3 days.")
        time.sleep(1)

        # Special Notes
        print("      Entering Patient Advice...")
        notes_input = driver.find_element(By.ID, "live_special_notes")
        notes_input.clear()
        notes_input.send_keys("Avoid cold drinks. Take medicine after food.")
        time.sleep(1)

        # Prescription
        print("      Entering Prescription...")
        presc_input = driver.find_element(By.ID, "live_prescription")
        presc_input.clear()
        presc_input.send_keys("Tab. Paracetamol 500mg - 1-0-1 - 3 Days\nSyp. Cough Relief - 5ml - 1-1-1 - 5 Days")
        time.sleep(STEP_DELAY)

        # 5. FINALIZE
        print("[5/5] Finalizing Consultation...")
        # Scroll to button if needed
        finalize_btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Finalize & Close Appointment')]")
        driver.execute_script("arguments[0].scrollIntoView();", finalize_btn)
        time.sleep(1)
        finalize_btn.click()

        print("      Waiting for submission confirmation...")
        wait.until(EC.url_contains("doctor_dashboard.php"))
        
        # Check for success notification
        try:
            success_box = wait.until(EC.visibility_of_element_located((By.XPATH, "//*[contains(text(), 'Success')]")))
            print(f"      SUCCESS: {success_box.text}")
        except:
            print("      Consultation finalized successfully.")

        time.sleep(STEP_DELAY * 2)

    except Exception as e:
        print(f"\n>>> AUTOMATION ERROR: {str(e)}")
        # Optional: Save page source for debugging
        # with open("debug_page_source.html", "w") as f: f.write(driver.page_source)
    
    finally:
        driver.quit()
        print(">>> Browser closed.")

if __name__ == "__main__":
    run_consultation_automation()
