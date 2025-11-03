#!/usr/bin/env python3
"""
Admin durations management test module.
Tests admin duration CRUD operations including delete functionality.
"""

import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException, ElementClickInterceptedException

def test_admin_durations_delete():
    """Test admin duration delete functionality"""

    # Set up Chrome options
    chrome_options = Options()
    # chrome_options.add_argument('--headless')  # Commented out to show browser
    chrome_options.add_argument('--no-sandbox')
    chrome_options.add_argument('--disable-dev-shm-usage')

    driver = None
    try:
        # Initialize driver
        driver = webdriver.Chrome(options=chrome_options)
        wait = WebDriverWait(driver, 10)

        print("Chrome driver initialized")

        # Navigate to login page
        driver.get('http://localhost:8000/login')
        print("Navigated to login page")

        # Wait for page to load
        wait.until(EC.presence_of_element_located((By.ID, 'loginForm')))

        # Fill in login credentials
        email_field = driver.find_element(By.ID, 'email')
        password_field = driver.find_element(By.ID, 'password')

        email_field.clear()
        email_field.send_keys('admin@example.com')
        print("✓ Entered admin email")

        password_field.clear()
        password_field.send_keys('password')
        print("✓ Entered admin password")

        # Submit the form
        login_button = driver.find_element(By.ID, 'loginBtn')
        login_button.click()
        print("✓ Clicked login button")

        # Wait for redirect to admin dashboard
        wait.until(lambda driver: 'admin' in driver.current_url)
        print("✓ Successfully logged in as admin")

        # Navigate to durations page
        driver.get('http://localhost:8000/admin/durations')
        print("✓ Navigated to durations page")

        # Wait for page to load
        wait.until(EC.presence_of_element_located((By.ID, 'durations-table')))
        print("✓ Durations table loaded")

        # Check if there are any durations in the table
        table_body = driver.find_element(By.CSS_SELECTOR, '#durations-table tbody')
        rows = table_body.find_elements(By.TAG_NAME, 'tr')

        if len(rows) == 1 and 'No durations found' in rows[0].text:
            print("⚠ No durations available to delete - creating one first")

            # Click "Add Duration" button
            add_button = driver.find_element(By.CSS_SELECTOR, '[data-target="#createDurationModal"]')
            add_button.click()
            print("✓ Clicked Add Duration button")

            # Wait for modal to appear
            wait.until(EC.visibility_of_element_located((By.ID, 'createDurationModal')))

            # Fill in the form
            minutes_field = driver.find_element(By.ID, 'modal_minutes')
            minutes_field.clear()
            minutes_field.send_keys('45')

            duration_type_select = driver.find_element(By.ID, 'modal_duration_type')
            duration_type_select.find_element(By.CSS_SELECTOR, 'option[value="general"]').click()

            price_field = driver.find_element(By.ID, 'modal_price')
            price_field.clear()
            price_field.send_keys('75000')

            # Submit the form
            create_button = driver.find_element(By.ID, 'createDurationBtn')
            create_button.click()
            print("✓ Submitted create duration form")

            # Wait for modal to close and success message
            wait.until(EC.invisibility_of_element_located((By.ID, 'createDurationModal')))
            time.sleep(2)  # Extra wait for AJAX

            # Refresh the page to see the new duration
            driver.refresh()
            wait.until(EC.presence_of_element_located((By.ID, 'durations-table')))

            # Re-get the rows
            table_body = driver.find_element(By.CSS_SELECTOR, '#durations-table tbody')
            rows = table_body.find_elements(By.TAG_NAME, 'tr')

        # Find a duration to delete (skip the "no durations" row if it exists)
        duration_row = None
        for row in rows:
            if 'No durations found' not in row.text and row.is_displayed():
                duration_row = row
                break

        if not duration_row:
            print("✗ No deletable durations found")
            return False

        # Get duration info for verification
        duration_cells = duration_row.find_elements(By.TAG_NAME, 'td')
        duration_id = duration_cells[0].text.strip()
        duration_name = duration_cells[1].text.strip()
        print(f"✓ Found duration to delete: ID {duration_id} - {duration_name}")

        # Click the delete button
        delete_button = duration_row.find_element(By.CSS_SELECTOR, '[data-target="#deleteDurationModal"]')
        delete_button.click()
        print("✓ Clicked delete button")

        # Wait for modal to appear
        wait.until(EC.visibility_of_element_located((By.ID, 'deleteDurationModal')))
        print("✓ Delete confirmation modal appeared")

        # Click the confirm delete button
        confirm_delete_button = driver.find_element(By.ID, 'deleteDurationLink')
        delete_url = confirm_delete_button.get_attribute('href')
        print(f"✓ Delete URL: {delete_url}")

        # Verify the URL is correct (should be /admin/durations/delete/{id})
        if f'/admin/durations/delete/{duration_id}' not in delete_url:
            print(f"✗ Incorrect delete URL: {delete_url}")
            return False

        confirm_delete_button.click()
        print("✓ Clicked confirm delete button")

        # Wait for redirect and check result
        print("Waiting for redirect...")
        time.sleep(5)  # Wait longer for redirect

        current_url = driver.current_url
        print(f"✓ Current URL after delete: {current_url}")

        # Check if we were redirected back to durations index
        if '/admin/durations' in current_url and '/delete/' not in current_url:
            print("✓ Successfully redirected to durations index")
        else:
            print(f"⚠ Still on delete URL: {current_url}")

        # Check for success or error messages
        try:
            success_alert = driver.find_element(By.CLASS_NAME, 'alert-success')
            print(f"✓ Success message: {success_alert.text}")
            return True
        except NoSuchElementException:
            try:
                error_alert = driver.find_element(By.CLASS_NAME, 'alert-danger')
                error_text = error_alert.text
                print(f"⚠ Error message: {error_text}")

                # If it's the expected error about associated appointments, that's still a success
                if 'associated with it' in error_text.lower() or 'appointments' in error_text.lower():
                    print("✓ Delete properly prevented due to associated appointments")
                    return True
                else:
                    print("✗ Unexpected error during delete")
                    return False
            except NoSuchElementException:
                # Check if we're on the durations index page
                if '/admin/durations' in current_url and '/delete/' not in current_url:
                    print("✓ On durations index page (no message needed)")
                    return True
                else:
                    print("✗ No success/error message found and not properly redirected")
                    print("Checking page content...")
                    try:
                        page_title = driver.find_element(By.TAG_NAME, 'h1')
                        print(f"Page title: {page_title.text}")
                    except:
                        print("Could not find page title")
                    return False

    except Exception as e:
        print(f"✗ Test failed with error: {str(e)}")
        import traceback
        traceback.print_exc()
        return False

    finally:
        if driver:
            driver.quit()
            print("Driver closed")

def run_admin_durations_delete_test():
    """Run the admin durations delete test"""
    print("Testing admin durations delete functionality...")
    print('='*50)
    success = test_admin_durations_delete()
    print('='*50)
    if success:
        print("✓ Admin durations delete test PASSED")
    else:
        print("✗ Admin durations delete test FAILED")
    return success

if __name__ == "__main__":
    run_admin_durations_delete_test()