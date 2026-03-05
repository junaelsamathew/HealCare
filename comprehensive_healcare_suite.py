import time
import unittest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException, WebDriverException

# CONFIGURATION
BASE_URL = "http://localhost/HealCare-main"
# Credentials (fetched from system mapping)
DEFAULT_PASS = "Zoom#2023"
ADMIN_PASS = "admin@Healcare"

CREDENTIALS = {
    "admin": {"email": "admin@gmail.com", "pass": ADMIN_PASS},
    "patient": {"email": "akshaykrishnar2028@mca.ajce.in", "pass": DEFAULT_PASS},
    "doctor": {"email": "mary.mariam@healcare.com", "pass": DEFAULT_PASS},
    "nurse": {"email": "gigi.tony@healcare.com", "pass": DEFAULT_PASS},
    "lab_staff": {"email": "ciya.john@healcare.com", "pass": DEFAULT_PASS},
    "pharmacist": {"email": "mini.jose@healcare.com", "pass": DEFAULT_PASS},
    "receptionist": {"email": "ancy.james@healcare.com", "pass": DEFAULT_PASS},
    "canteen_staff": {"email": "riya.shibu@healcare.com", "pass": DEFAULT_PASS}
}

SLEEP_TIME = 3 # Slow movement for observation

class HealCareComprehensiveSuite(unittest.TestCase):
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

    def login(self, email, password):
        self.step_log(f"Logging in with {email}...")
        self.driver.get(f"{BASE_URL}/login.php")
        email_input = self.wait.until(EC.presence_of_element_located((By.NAME, "identity")))
        email_input.clear()
        email_input.send_keys(email)
        pass_input = self.driver.find_element(By.NAME, "password")
        pass_input.clear()
        pass_input.send_keys(password)
        self.slow_pause()
        self.driver.find_element(By.CLASS_NAME, "btn-login").click()
        time.sleep(2)

    def logout(self):
        self.step_log("Logging out...")
        try:
            self.slow_pause()
            logout_btn = self.wait.until(EC.element_to_be_clickable((By.CLASS_NAME, "btn-logout")))
            logout_btn.click()
            time.sleep(1)
        except:
            self.driver.get(f"{BASE_URL}/logout.php")
        self.step_log("Logout complete.")

    # --- 1. PATIENT ROLE ---
    def test_01_patient_journey(self):
        print("\n--- TEST: PATIENT ROLE START ---")
        self.login(CREDENTIALS["patient"]["email"], CREDENTIALS["patient"]["pass"])
        driver = self.driver
        wait = self.wait

        # Appointment Booking
        self.step_log("Navigating to Appointment Form...")
        driver.get(f"{BASE_URL}/appointment_form.php")
        self.slow_pause()
        try:
            dept_select = wait.until(EC.presence_of_element_located((By.NAME, "dept")))
            driver.execute_script("arguments[0].value = 'General Medicine / Cardiovascular'; arguments[0].dispatchEvent(new Event('change'));", dept_select)
            time.sleep(2)
            doc_select = wait.until(EC.presence_of_element_located((By.NAME, "doctor_id")))
            driver.execute_script("arguments[0].selectedIndex = 1; arguments[0].dispatchEvent(new Event('change'));", doc_select)
            time.sleep(2)
            slot = wait.until(EC.element_to_be_clickable((By.CLASS_NAME, "slot-chip")))
            driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", slot)
            slot.click()
            self.step_log("Selected time slot.")
            driver.find_element(By.ID, "symptoms_reason").send_keys("Automated Testing.")
            driver.find_element(By.ID, "captchaInput").send_keys("5692")
            self.step_log("Form populated.")
            self.slow_pause()
        except: self.step_log("Booking form skip/fail.")

        # Billing & Canteen
        self.step_log("Checking Billing & Canteen status...")
        driver.get(f"{BASE_URL}/billing.php")
        self.slow_pause()
        driver.get(f"{BASE_URL}/canteen.php")
        self.slow_pause()

        # Feedback Wizard
        self.step_log("Running Feedback Wizard steps...")
        driver.get(f"{BASE_URL}/patient_feedback.php")
        self.slow_pause()
        try:
            wait.until(EC.presence_of_element_located((By.XPATH, "//*[contains(text(), 'Excellent')]"))).click()
            self.slow_pause()
            wait.until(EC.presence_of_element_located((By.XPATH, "//*[contains(text(), 'Satisfied')]"))).click()
            self.slow_pause()
            driver.find_elements(By.CLASS_NAME, "fa-star")[4].click()
            self.slow_pause()
            wait.until(EC.presence_of_element_located((By.XPATH, "//*[contains(text(), 'Quick Response')]"))).click()
            self.slow_pause()
            driver.find_element(By.ID, "commentBox").send_keys("Slow automated test works.")
            self.step_log("Feedback populated.")
            self.slow_pause()
        except: pass

        self.logout()
        print("--- TEST: PATIENT ROLE COMPLETE ---")

    # --- 2. DOCTOR ROLE ---
    def test_02_doctor_journey(self):
        print("\n--- TEST: DOCTOR ROLE START ---")
        self.login(CREDENTIALS["doctor"]["email"], CREDENTIALS["doctor"]["pass"])
        driver = self.driver
        
        self.step_log("Checking Doctor Dashboard stats...")
        self.slow_pause()
        
        sections = ["Patients", "Appointments", "Prescriptions", "Lab Orders", "Apply Leave"]
        for sec in sections:
            self.step_log(f"Navigating to {sec} section...")
            try:
                driver.find_element(By.LINK_TEXT, sec).click()
                self.slow_pause()
            except: self.step_log(f"Could not click {sec}")

        self.logout()
        print("--- TEST: DOCTOR ROLE COMPLETE ---")

    # --- 3. ADMIN ROLE ---
    def test_03_admin_journey(self):
        print("\n--- TEST: ADMIN ROLE START ---")
        self.login(CREDENTIALS["admin"]["email"], CREDENTIALS["admin"]["pass"])
        driver = self.driver

        self.step_log("Accessing User Management...")
        driver.get(f"{BASE_URL}/admin_dashboard.php?section=users")
        self.slow_pause()
        try:
            search = self.wait.until(EC.presence_of_element_located((By.ID, "userSearch")))
            search.send_keys("akshay")
            self.step_log("Searching for user...")
            self.slow_pause()
        except: pass

        self.step_log("Checking Pharmacy Inventory...")
        driver.find_element(By.LINK_TEXT, "Pharmacy & Inventory").click()
        self.slow_pause()

        self.step_log("Checking Insurance Module...")
        driver.find_element(By.LINK_TEXT, "Insurance Management").click()
        self.slow_pause()

        self.logout()
        print("--- TEST: ADMIN ROLE COMPLETE ---")

    # --- 4. STAFF ROLES ---
    def test_04_staff_dashboards(self):
        print("\n--- TEST: STAFF DASHBOARDS START ---")
        roles = ["receptionist", "nurse", "pharmacist", "lab_staff", "canteen_staff"]
        for role in roles:
            self.step_log(f"Testing {role.upper()} access...")
            self.login(CREDENTIALS[role]["email"], CREDENTIALS[role]["pass"])
            self.step_log(f"{role.upper()} Dashboard landed.")
            self.slow_pause()
            self.logout()
        print("--- TEST: STAFF DASHBOARDS COMPLETE ---")

if __name__ == "__main__":
    unittest.main()
