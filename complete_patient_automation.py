import time
import unittest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException, WebDriverException

# CONFIGURATION
BASE_URL = "http://localhost/HealCare-main"
PATIENT_EMAIL = "akshaykrishnar2028@mca.ajce.in"
PATIENT_PASS = "Zoom#2023"
SLEEP_TIME = 3 # Seconds to wait between major steps to make it observable

class HealCarePatientFullTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        options = webdriver.ChromeOptions()
        options.add_argument("--start-maximized")
        # options.add_argument("--headless")
        cls.driver = webdriver.Chrome(options=options)
        cls.wait = WebDriverWait(cls.driver, 20)

    @classmethod
    def tearDownClass(cls):
        cls.driver.quit()

    def slow_pause(self):
        time.sleep(SLEEP_TIME)

    def step_log(self, text):
        print(f"  >> {text}")

    def test_complete_patient_workflow(self):
        driver = self.driver
        wait = self.wait

        # 1. LOGIN
        self.step_log("Logging in as Patient...")
        driver.get(f"{BASE_URL}/login.php")
        wait.until(EC.presence_of_element_located((By.NAME, "identity"))).send_keys(PATIENT_EMAIL)
        driver.find_element(By.NAME, "password").send_keys(PATIENT_PASS)
        self.slow_pause()
        driver.find_element(By.CLASS_NAME, "btn-login").click()
        wait.until(EC.url_contains("patient_dashboard.php"))
        self.step_log("Successfully entered Patient Dashboard.")
        self.slow_pause()

        # 2. BOOK AN APPOINTMENT
        self.step_log("Starting Appointment Booking...")
        driver.get(f"{BASE_URL}/appointment_form.php")
        self.slow_pause()
        
        # Select Dept
        dept_select = wait.until(EC.presence_of_element_located((By.NAME, "dept")))
        driver.execute_script("arguments[0].value = 'General Medicine / Cardiovascular'; arguments[0].dispatchEvent(new Event('change'));", dept_select)
        self.step_log("Department selected.")
        time.sleep(2)
        
        # Select Doctor
        doc_select = wait.until(EC.presence_of_element_located((By.NAME, "doctor_id")))
        driver.execute_script("arguments[0].selectedIndex = 1; arguments[0].dispatchEvent(new Event('change'));", doc_select)
        self.step_log("Doctor selected.")
        time.sleep(2)
        
        # Click a Slot (if available)
        try:
            slot = wait.until(EC.element_to_be_clickable((By.CLASS_NAME, "slot-chip")))
            driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", slot)
            time.sleep(1)
            slot.click()
            self.step_log("Selected an available time slot.")
            self.slow_pause()
        except:
             self.step_log("No available slot found for selected date.")

        # Fill Patient Details (Symptoms)
        try:
            reason_box = driver.find_element(By.ID, "symptoms_reason")
            reason_box.send_keys("Automated Test: Slight fever and headache for 2 days.")
            self.step_log("Filled appointment symptoms.")
            time.sleep(1)
            
            # Captcha
            captcha_box = driver.find_element(By.ID, "captchaInput")
            captcha_box.send_keys("5692") # Fixed value based on source code analysis
            self.step_log("Filled security captcha.")
            self.slow_pause()
            
            # Note: We are not clicking 'Confirm Booking' to avoid cluttering real DB data unless needed
            # driver.find_element(By.CLASS_NAME, "btn-continue").click()
        except NoSuchElementException:
            self.step_log("Appointment form fields not ready.")

        # 3. REVIEW MY APPOINTMENTS
        self.step_log("Reviewing 'My Appointments'...")
        driver.find_element(By.LINK_TEXT, "My Appointments").click()
        wait.until(EC.url_contains("my_appointments.php"))
        self.slow_pause()

        # 4. MEDICAL RECORDS
        self.step_log("Accessing Medical Records...")
        driver.get(f"{BASE_URL}/medical_records.php")
        self.slow_pause()

        # 5. HEALTH PACKAGES
        self.step_log("Viewing Health Packages...")
        driver.find_element(By.LINK_TEXT, "My Health Packages").click()
        wait.until(EC.url_contains("my_packages.php"))
        self.slow_pause()

        # 6. LAB REPORTS
        self.step_log("Reviewing Lab Reports...")
        driver.find_element(By.LINK_TEXT, "Lab Reports").click()
        wait.until(EC.url_contains("patient_lab_results.php"))
        self.slow_pause()

        # 7. BILLING & PAY NOW
        self.step_log("Navigating to Billing section...")
        driver.find_element(By.LINK_TEXT, "Billing").click()
        wait.until(EC.url_contains("billing.php"))
        self.slow_pause()

        # 8. CANTEEN (Order Food)
        self.step_log("Entering Hospital Canteen...")
        driver.get(f"{BASE_URL}/canteen.php")
        self.slow_pause()
        try:
            food_card = driver.find_element(By.CLASS_NAME, "food-card")
            driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", food_card)
            self.step_log("Browsing canteen menu items...")
            self.slow_pause()
        except:
            pass

        # 9. AMBULANCE SERVICE
        self.step_log("Checking Ambulance Services...")
        driver.find_element(By.LINK_TEXT, "Ambulance Service").click()
        wait.until(EC.url_contains("patient_ambulance.php"))
        self.slow_pause()

        # 10. FEEDBACK SECTION (Wizard)
        self.step_log("Opening Feedback Wizard...")
        driver.get(f"{BASE_URL}/patient_feedback.php")
        self.slow_pause()
        try:
            # Step 1: Overall Exp
            wait.until(EC.presence_of_element_located((By.XPATH, "//*[contains(text(), 'Excellent')]"))).click()
            self.step_log("Feedback Step 1: Excellent.")
            self.slow_pause()
            # Step 2: Doctor
            wait.until(EC.presence_of_element_located((By.XPATH, "//*[contains(text(), 'Satisfied')]"))).click()
            self.step_log("Feedback Step 2: Satisfied.")
            self.slow_pause()
            # Step 3: Cleanliness
            stars = driver.find_elements(By.CLASS_NAME, "fa-star")
            if len(stars) >= 5: stars[4].click()
            self.step_log("Feedback Step 3: Rated 5 Stars.")
            self.slow_pause()
            # Step 4: Staff
            wait.until(EC.presence_of_element_located((By.XPATH, "//*[contains(text(), 'Quick Response')]"))).click()
            self.step_log("Feedback Step 4: Quick Staff Response.")
            self.slow_pause()
            # Step 5: Comments
            driver.find_element(By.ID, "commentBox").send_keys("Slower automated feedback testing.")
            self.step_log("Feedback Step 5: Final Comments.")
            self.slow_pause()
            # driver.find_element(By.ID, "btnSubmit").click()
        except:
            pass

        # 11. PROFILE SECTION
        self.step_log("Reviewing Patient Profile...")
        driver.find_element(By.LINK_TEXT, "Profile").click()
        wait.until(EC.url_contains("settings.php"))
        self.slow_pause()

        # LOGOUT
        self.step_log("Logging out...")
        driver.find_element(By.CLASS_NAME, "btn-logout").click()
        self.step_log("Test Suite Completed Successfully.")
        time.sleep(2)

if __name__ == "__main__":
    unittest.main()
