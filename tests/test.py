#Traditional Course Assumptions:
#This test script assumes the existence of data for either a spring or a fall semester
#For any arbitrary spring/fall semester, this script assumes that COMM-1113 exists and has more than 1 section
#For any arbitrary spring/fall semester, this script assumes that COSC-2203 exists and has exactly 1 section
#For any arbitrary spring/fall semester, this script assumes the first department (alphabetically) has at least 2 classes
#
#This script assumes the existence of data for at least 1 summer semester
#
#Non-Traditional Course Assumptions:
#This test script assumes the existence of data for at least 1 semester
#
#Professor Assumptions:
#This test script assumes the existence of data for at least 1 semester

from selenium import webdriver
from selenium.common.exceptions import TimeoutException
from selenium.webdriver.support.ui import WebDriverWait # available since 2.4.0
import time

# Create a new instance of the Chrome driver
driver = webdriver.Chrome()

singleSection = ("COSC", "COSC-2203")
multiSection = ("COMM", "COMM-1113")

def getSections(driver):
    classDD = driver.find_elements_by_tag_name("select")[1]
    return map(lambda x:x.text, classDD.find_elements_by_tag_name("option"))[1:]

def getClasses(driver, num):
    classDD = driver.find_elements_by_class_name("chzn-drop")[num]
    return filter(lambda x:x, map(lambda x:x.get_attribute("data-value"), classDD.find_elements_by_tag_name("li")))

def getClassObjects(driver, num):
    classDD = driver.find_elements_by_class_name("chzn-drop")[num]
    return filter(lambda x:x, classDD.find_elements_by_tag_name("li"))

def selectDepartment(driver, dept, num):
    print "Selecting department %s in department dropdown %s" % (dept, num)
    classDD = driver.find_elements_by_tag_name("select")[num*2+1]
    classDD.click()
    for option in classDD.find_elements_by_tag_name("option"):
        if option.text == dept:
            option.click()
            break
    WebDriverWait(driver, 5).until(lambda driver: len(driver.find_elements_by_tag_name("select")) > num*2+3)
    WebDriverWait(driver, 5).until(lambda driver: len(driver.find_elements_by_class_name("chzn-single")) > num)

def selectClass(driver, target, num):
    print "Selecting class %s in class dropdown %s" % (target, num)
    classDD = driver.find_elements_by_class_name("chzn-single")[num]
    classDD.click()
    for option in getClassObjects(driver, num):
        if option.get_attribute("data-value") == target:
            option.click()
            break
    WebDriverWait(driver, 5).until(lambda driver: driver.find_elements_by_class_name(target))

driver.get("http://localhost/~bion/lusa")
#driver.get("http://www.bionoren.com/letu")
WebDriverWait(driver, 5).until(lambda driver: driver.find_elements_by_tag_name("select"))
driver.get_screenshot_as_file("01-init.png")

print "Testing traditional student"
print "---------------------------"
print "-Testing single section class"
selectDepartment(driver, singleSection[0], 0)
driver.get_screenshot_as_file("02-dept.png")
selectClass(driver, singleSection[1], 0);
driver.get_screenshot_as_file("03-class.png")

print "-Testing multiple section class"
selectDepartment(driver, multiSection[0], 1)
selectClass(driver, multiSection[1], 1);
driver.get_screenshot_as_file("04-multi-classes.png");
multiHeader = driver.find_elements_by_class_name(multiSection[1])[0]
multiHeader.click()
driver.get_screenshot_as_file("05-multi-classes-open.png");
multiHeader.click()

print "-Testing multiple class selections"
sections = getSections(driver)
selectDepartment(driver, sections[0], 2)
classes = getClasses(driver, 2)
selectClass(driver, classes[0], 2);
selectClass(driver, classes[1], 2);
driver.get_screenshot_as_file("06-multi-choice.png");
multiHeader = driver.find_elements_by_class_name(classes[1])[0]
multiHeader.click()
driver.get_screenshot_as_file("07-multi-choice-open.png");
multiHeader.click()

print "-Change class selection"
selectClass(driver, singleSection[1], 0);
classes = getClasses(driver, 0)
selectClass(driver, classes[0], 0);
driver.get_screenshot_as_file("08-class-changed.png")

print "-Change department selection" #This test is broken because the chrome webdriver is broken
sections = getSections(driver)
selectDepartment(driver, sections[1], 0);
time.sleep(5)
#WebDriverWait(driver, 5).until(lambda driver: not driver.find_elements_by_class_name(singleSection[1]))
driver.get_screenshot_as_file("09-dept-changed.png")

print "Tests Complete"
driver.quit()