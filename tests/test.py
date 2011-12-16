from selenium import webdriver
from selenium.common.exceptions import TimeoutException
from selenium.webdriver.support.ui import WebDriverWait # available since 2.4.0
import time

# Create a new instance of the Chrome driver
driver = webdriver.Chrome()

def selectFirstClass(driver):
    classDD = driver.find_elements_by_tag_name("select")[1]
    classDD.click()
    for option in classDD.find_elements_by_tag_name("option"):
        if option.text == "COSC":
            option.click()
            break
    WebDriverWait(driver, 1).until(lambda driver: len(driver.find_elements_by_tag_name("select")) > 1)

# go to the google home page
driver.get("http://localhost/~bion/lusa")
WebDriverWait(driver, 5).until(lambda driver: driver.find_elements_by_tag_name("select"))
selectFirstClass(driver)
selectFirstClass(driver) #You shouldn't have to do this twice...
WebDriverWait(driver, 5).until(lambda driver: len(driver.find_elements_by_tag_name("select")) == 4)
driver.get_screenshot_as_file("init.png")
driver.quit()