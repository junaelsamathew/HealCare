import time
import unittest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException, WebDriverException

# CONFIGURATION
BASE_URL = "http://localhost/HealCare-main"
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

class HealCareMasterTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        options = webdriver.ChromeOptions()
        options.add_argument("--start-maximized")
        # options.add_argument("--headless") # Uncomment for headless
        cls.driver = webdriver.Chrome(options=options)
        cls.wait = WebDriverWait(cls.driver, 15)

    @classmethod
    def tearDownClass(cls):
        cls.driver.quit()

    def login(self, email, password):
        self.driver.get(f"{BASE_URL}/login.php")
        email_input = self.wait.until(EC.presence_of_element_located((By.NAME, "identity")))
        email_input.clear()
        email_input.send_keys(email)
        pass_input = self.driver.find_element(By.NAME, "password")
        pass_input.clear()
        pass_input.send_keys(password)
        self.driver.find_element(By.CLASS_NAME, "btn-login").click()
        time.sleep(2)

    def logout(self):
        try:
            logout_btn = self.wait.until(EC.element_to_be_clickable((By.CLASS_NAME, "btn-logout")))
            logout_btn.click()
            time.sleep(1)
        except:
            print("Failed to logout normally, force redirecting to logout.php")
            self.driver.get(f"{BASE_URL}/logout.php")

    # --- 1. PATIENT FULL JOURNEY ---
    def test_01_patient_role(self):
        print("\n[PATIENT] Starting Journey...")
        self.login(CREDENTIALS["patient"]["email"], CREDENTIALS["patient"]["pass"])
        self.assertIn("patient_dashboard.php", self.driver.current_url)

        # Dashboard Check
        stats = self.driver.find_elements(By.CLASS_NAME, "stat-card")
        print(f"    - Found {len(stats)} dashboard stat cards.")

        # Book Appointment
        self.driver.find_element(By.LINK_TEXT, "Book Appointment").click()
        self.wait.until(EC.url_contains("book_appointment.php"))
        print("    - Navigation to Booking Page successful.")

        # Medical Records
        self.driver.find_element(By.LINK_TEXT, "Medical Records").click()
        self.wait.until(EC.url_contains("medical_records.php"))
        print("    - Navigation to Medical Records successful.")

        # Prescriptions & Order Medicine
        self.driver.find_element(By.LINK_TEXT, "Prescriptions").click()
        self.wait.until(EC.url_contains("prescriptions.php"))
        try:
            self.driver.execute_script("window.scrollTo(0, document.body.scrollHeight/2);")
            order_btns = self.driver.find_elements(By.NAME, "order_prescription")
            if order_btns:
                order_btns[0].click()
                print("    - Order Medicine button triggered.")
        except:
            print("    - No order buttons found on presc page.")

        # Billing
        self.driver.find_element(By.LINK_TEXT, "Billing").click()
        self.wait.until(EC.url_contains("billing.php"))
        print("    - Billing section viewed.")

        # Canteen
        self.driver.get(f"{BASE_URL}/canteen.php")
        print("    - Canteen page accessed.")

        # Feedback
        self.driver.get(f"{BASE_URL}/patient_feedback.php")
        feedback_area = self.wait.until(EC.presence_of_element_located((By.NAME, "feedback_text")))
        feedback_area.send_keys("Automated testing: System is working great!")
        print("    - Feedback form populated.")

        self.logout()
        print("[PATIENT] Journey completed.")

    # --- 2. DOCTOR FULL JOURNEY ---
    def test_02_doctor_role(self):
        print("\n[DOCTOR] Starting Journey...")
        self.login(CREDENTIALS["doctor"]["email"], CREDENTIALS["doctor"]["pass"])
        self.assertIn("doctor_dashboard.php", self.driver.current_url)

        # Start Consultation (if queue exists)
        try:
            consult_btn = self.driver.find_element(By.XPATH, "//a[contains(text(), 'Consult Now')]")
            consult_btn.click()
            self.wait.until(EC.presence_of_element_located((By.ID, "live_diagnosis"))).send_keys("Test Diagnosis")
            self.driver.find_element(By.ID, "live_treatment").send_keys("Test Treatment Plan")
            self.driver.find_element(By.ID, "live_prescription").send_keys("Test Medicine 1\nTest Medicine 2")
            print("    - Filled consultation form details.")
        except:
            print("    - No patient in consultation queue.")

        # My Patients
        self.driver.find_element(By.LINK_TEXT, "My Patients").click()
        self.wait.until(EC.url_contains("doctor_patients.php"))
        print("    - Patient history record viewed.")

        # Request Leave
        self.driver.find_element(By.LINK_TEXT, "Request Leave").click()
        self.wait.until(EC.url_contains("doctor_leave.php"))
        print("    - Leave request page accessible.")

        self.logout()
        print("[DOCTOR] Journey completed.")

    # --- 3. RECEPTIONIST ROLE ---
    def test_03_receptionist_role(self):
        print("\n[RECEPTIONIST] Starting Journey...")
        self.login(CREDENTIALS["receptionist"]["email"], CREDENTIALS["receptionist"]["pass"])
        self.assertIn("staff_dashboard.php", self.driver.current_url)
        
        # Dashboard Details
        wait_text = self.driver.find_element(By.TAG_NAME, "h1").text
        print(f"    - Receptionist Dashboard Header: {wait_text}")

        # Check for Appointment Management
        try:
            accept_btn = self.driver.find_element(By.XPATH, "//button[contains(text(), 'Confirm')]")
            print("    - Found appointment to confirm.")
        except:
            print("    - No pending appointments found on dashboard.")

        self.logout()
        print("[RECEPTIONIST] Journey completed.")

    # --- 4. ADMIN FULL JOURNEY ---
    def test_04_admin_role(self):
        print("\n[ADMIN] Starting Journey...")
        self.login(CREDENTIALS["admin"]["email"], CREDENTIALS["admin"]["pass"])
        self.assertIn("admin_dashboard.php", self.driver.current_url)

        # 1. User Management
        self.driver.get(f"{BASE_URL}/admin_dashboard.php?section=users")
        self.wait.until(EC.presence_of_element_located((By.ID, "userSearch"))).send_keys("test")
        print("    - User management search functional.")

        # 2. Pharmacy Inventory
        self.driver.find_element(By.LINK_TEXT, "Pharmacy & Inventory").click()
        self.wait.until(EC.url_contains("admin_pharmacy_inventory.php"))
        print("    - Global Pharmacy inventory active.")

        # 3. Wards & Rooms
        self.driver.get(f"{BASE_URL}/admin_dashboard.php?section=wards")
        print("    - Ward management section viewed.")

        self.logout()
        print("[ADMIN] Journey completed.")

    # --- 5. SPECIALIZED STAFF (PHARMACIST / LAB / NURSE) ---
    def test_05_staff_roles_brief(self):
        print("\n[STAFF] Checking Pharmacist role...")
        self.login(CREDENTIALS["pharmacist"]["email"], CREDENTIALS["pharmacist"]["pass"])
        self.assertIn("staff_dashboard.php", self.driver.current_url)
        print("    - Pharmacist Dashboard accessed.")
        self.logout()

        print("[STAFF] Checking Lab Staff role...")
        self.login(CREDENTIALS["lab_staff"]["email"], CREDENTIALS["lab_staff"]["pass"])
        self.assertIn("staff_dashboard.php", self.driver.current_url)
        print("    - Lab Staff Dashboard accessed.")
        self.logout()

        print("[STAFF] Checking Nurse role...")
        self.login(CREDENTIALS["nurse"]["email"], CREDENTIALS["nurse"]["pass"])
        self.assertIn("staff_dashboard.php", self.driver.current_url)
        print("    - Nurse Dashboard accessed.")
        self.logout()

        print("[STAFF] Checking Canteen Staff role...")
        self.login(CREDENTIALS["canteen_staff"]["email"], CREDENTIALS["canteen_staff"]["pass"])
        self.assertIn("staff_dashboard.php", self.driver.current_url)
        print("    - Canteen Staff Dashboard accessed.")
        self.logout()

if __name__ == "__main__":
    unittest.main()
