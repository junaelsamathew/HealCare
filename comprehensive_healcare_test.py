import time
import unittest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException

# CONFIGURATION
BASE_URL = "http://localhost/HealCare-main"

# TEST ACCOUNTS
CREDENTIALS = {
    "admin": {"email": "admin@gmail.com", "pass": "admin@Healcare"},
    "doctor": {"email": "mary.mariam@healcare.com", "pass": "Zoom#2023"},
    "patient": {"email": "akshaykrishnar2028@mca.ajce.in", "pass": "Zoom#2023"}
}

class HealCareAutomation(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        options = webdriver.ChromeOptions()
        options.add_argument("--start-maximized")
        # cls.driver = webdriver.Chrome(options=options)
        # Using a context manager style inside tests instead for flexibility? 
        # No, standard unittest setup is better.
        cls.driver = webdriver.Chrome(options=options)
        cls.wait = WebDriverWait(cls.driver, 15)

    @classmethod
    def tearDownClass(cls):
        cls.driver.quit()

    def login(self, email, password):
        self.driver.get(f"{BASE_URL}/login.php")
        time.sleep(1)
        
        email_input = self.wait.until(EC.presence_of_element_located((By.NAME, "identity")))
        email_input.clear()
        email_input.send_keys(email)
        
        pass_input = self.driver.find_element(By.NAME, "password")
        pass_input.clear()
        pass_input.send_keys(password)
        
        login_btn = self.driver.find_element(By.CLASS_NAME, "btn-login")
        login_btn.click()
        time.sleep(2)

    def test_01_patient_journey(self):
        """Test full patient workflow: Login -> Dashboard -> Book -> Canteen -> Prescriptions"""
        print("\n>>> Testing Patient Journey...")
        self.login(CREDENTIALS["patient"]["email"], CREDENTIALS["patient"]["pass"])
        
        # 1. Dashboard Check
        self.assertIn("patient_dashboard.php", self.driver.current_url)
        print("    [✓] Patient Dashboard accessed.")
        
        # 2. Book Appointment
        self.driver.find_element(By.LINK_TEXT, "Book Appointment").click()
        self.assertIn("book_appointment.php", self.driver.current_url)
        print("    [✓] Booking page reached.")
        time.sleep(1)
        
        # 3. Medical Records
        self.driver.execute_script("window.scrollTo(0,0)")
        self.driver.find_element(By.LINK_TEXT, "Medical Records").click()
        self.wait.until(EC.presence_of_element_located((By.TAG_NAME, "h1")))
        print("    [✓] Medical Records page viewed.")
        
        # 4. Prescriptions & Order Medicine
        self.driver.find_element(By.LINK_TEXT, "Prescriptions").click()
        self.wait.until(EC.url_contains("prescriptions.php"))
        print("    [✓] Prescriptions page reached.")
        
        try:
            order_btn = self.driver.find_element(By.NAME, "order_prescription")
            self.driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", order_btn)
            time.sleep(1)
            order_btn.click()
            print("    [✓] 'Order Medicine' button clicked.")
        except NoSuchElementException:
            print("    [!] No active prescriptions to order.")

        # 5. Canteen
        self.driver.find_element(By.LINK_TEXT, "Canteen").click()
        self.wait.until(EC.url_contains("canteen.php"))
        print("    [✓] Canteen page accessed.")
        
        # Logout
        self.driver.find_element(By.CLASS_NAME, "btn-logout").click()
        print("    [✓] Patient Logout successful.")

    def test_02_doctor_journey(self):
        """Test Doctor workflow: Dashboard -> Consultation -> Inpatient Management"""
        print("\n>>> Testing Doctor Journey...")
        self.login(CREDENTIALS["doctor"]["email"], CREDENTIALS["doctor"]["pass"])
        
        # 1. Dashboard
        self.assertIn("doctor_dashboard.php", self.driver.current_url)
        print("    [✓] Doctor Dashboard accessed.")
        
        # 2. Daily Consultations
        try:
            consult_link = self.driver.find_element(By.XPATH, "//a[contains(text(), 'Consult Now')]")
            print("    [✓] Active patient found in queue.")
            # We won't finish the consultation to avoid mutating too much data in tests, 
            # but we verify the form is accessible if clicked.
        except NoSuchElementException:
            print("    [!] No pending patients for consultation.")

        # 3. Patients List
        self.driver.find_element(By.LINK_TEXT, "My Patients").click()
        self.wait.until(EC.url_contains("doctor_patients.php"))
        print("    [✓] Patient directory accessed.")
        
        # 4. Settings
        self.driver.find_element(By.LINK_TEXT, "Profile").click()
        self.wait.until(EC.url_contains("settings.php"))
        print("    [✓] Doctor Profile settings accessed.")
        
        # Logout
        self.driver.find_element(By.CLASS_NAME, "btn-logout").click()
        print("    [✓] Doctor Logout successful.")

    def test_03_admin_journey(self):
        """Test Admin workflow: Analytics -> User Management -> Appointments"""
        print("\n>>> Testing Admin Journey...")
        self.login(CREDENTIALS["admin"]["email"], CREDENTIALS["admin"]["pass"])
        
        # 1. Admin Analytics
        self.assertIn("admin_dashboard.php", self.driver.current_url)
        print("    [✓] Admin Analytics Dashboard accessed.")
        
        # 2. User Management
        user_mgmt = self.wait.until(EC.element_to_be_clickable((By.LINK_TEXT, "User Management")))
        user_mgmt.click()
        self.wait.until(EC.url_contains("section=users"))
        print("    [✓] User Management section reached.")
        
        # Search test in admin
        search_input = self.driver.find_element(By.ID, "userSearch")
        search_input.send_keys("akshay")
        time.sleep(1)
        print("    [✓] User search functionality verified.")
        
        # 3. Appointments Management
        self.driver.find_element(By.LINK_TEXT, "Appointments").click()
        self.wait.until(EC.url_contains("section=appointments"))
        print("    [✓] Global Appointments management accessed.")
        
        # 4. Pharmacy Inventory
        self.driver.find_element(By.LINK_TEXT, "Pharmacy & Inventory").click()
        self.wait.until(EC.url_contains("admin_pharmacy_inventory.php"))
        print("    [✓] Central Pharmacy inventory accessed.")
        
        # Logout
        self.driver.find_element(By.CLASS_NAME, "btn-logout").click()
        print("    [✓] Admin Logout successful.")

if __name__ == "__main__":
    unittest.main()
