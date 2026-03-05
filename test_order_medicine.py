import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException

# CONFIGURATION
BASE_URL = "http://localhost/HealCare-main"
USER_EMAIL = "akshaykrishnar2028@mca.ajce.in"
USER_PASS = "Zoom#2023"

# DELAY CONFIGURATION (in seconds)
STEP_DELAY = 2

def run_order_medicine_automation():
    print(">>> Starting HealCare Order Medicine Automation")
    
    # Initialize WebDriver
    options = webdriver.ChromeOptions()
    options.add_argument("--start-maximized")
    driver = webdriver.Chrome(options=options)
    wait = WebDriverWait(driver, 15)

    try:
        # 1. LOGIN
        print("[1/4] Navigating to Login Page...")
        driver.get(f"{BASE_URL}/login.php")
        time.sleep(STEP_DELAY)

        print(f"      Entering Email: {USER_EMAIL}")
        email_input = wait.until(EC.visibility_of_element_located((By.NAME, "identity")))
        email_input.clear()
        email_input.send_keys(USER_EMAIL)
        
        print("      Entering Password...")
        pass_input = driver.find_element(By.NAME, "password")
        pass_input.clear()
        pass_input.send_keys(USER_PASS)
        time.sleep(1)

        print("      Clicking Login...")
        login_btn = driver.find_element(By.CLASS_NAME, "btn-login")
        login_btn.click()

        # 2. DASHBOARD
        print("[2/4] Waiting for Dashboard...")
        try:
            wait.until(EC.url_contains("patient_dashboard.php"))
            print("      Login Successful! Dashboard Accessed.")
        except TimeoutException:
            print("      TIMEOUT: Did not reach patient_dashboard.php")
            print(f"      Current URL: {driver.current_url}")
            raise Exception("Login redirection failed or timed out.")

        time.sleep(STEP_DELAY)

        # 3. GO TO PRESCRIPTIONS
        print("[3/4] Navigating to Prescriptions Section...")
        presc_link = wait.until(EC.element_to_be_clickable((By.XPATH, "//a[contains(@href, 'prescriptions.php')]")))
        presc_link.click()
        
        wait.until(EC.url_contains("prescriptions.php"))
        print("      Prescriptions page loaded.")
        time.sleep(STEP_DELAY)

        # 4. ORDER MEDICINE
        print("[4/4] Locating 'Order Medicines' button...")
        
        # Scroll down to see prescriptions
        driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
        time.sleep(STEP_DELAY)

        try:
            # Find the first "Order Medicines" button that is visible
            order_btn = wait.until(EC.element_to_be_clickable((By.NAME, "order_prescription")))
            print("      Found 'Order Medicines' button. Clicking it...")
            
            # Scroll element into view just in case
            driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", order_btn)
            time.sleep(1)
            order_btn.click()
            
            print("      Order button clicked.")
            time.sleep(STEP_DELAY)
            
            # Verify success message
            try:
                success_msg = wait.until(EC.visibility_of_element_located((By.XPATH, "//*[contains(text(), 'Order sent to pharmacy successfully')]")))
                print(f"      SUCCESS: {success_msg.text}")
            except TimeoutException:
                print("      Warning: Success message not found, but order button was clicked.")
                
        except TimeoutException:
            print("      ERROR: 'Order Medicines' button not found or not clickable.")
            print("      Possible reasons: No active prescriptions, or all have been ordered already.")
            # Optional: print page source for debugging
            # print(driver.page_source)
            raise Exception("No 'Order Medicines' button available.")

        print("\n>>> Automation Completed Successfully!")
        time.sleep(STEP_DELAY * 2)

    except Exception as e:
        print(f"\n>>> AUTOMATION ERROR: {str(e)}")
    
    finally:
        driver.quit()
        print(">>> Browser closed.")

if __name__ == "__main__":
    run_order_medicine_automation()
