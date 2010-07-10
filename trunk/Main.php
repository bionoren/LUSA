<?php
    /*
	 *	Copyright 2009 Bion Oren
	 *
	 *	Licensed under the Apache License, Version 2.0 (the "License");
	 *	you may not use this file except in compliance with the License.
	 *	You may obtain a copy of the License at
	 *		http://www.apache.org/licenses/LICENSE-2.0
	 *	Unless required by applicable law or agreed to in writing, software
	 *	distributed under the License is distributed on an "AS IS" BASIS,
	 *	WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
	 *	See the License for the specific language governing permissions and
	 *	limitations under the License.
	 */

    /**
     * Handles processing of the main page.
     *
     * Provides user input to other classes that need it and holds intermediary
     * class information arrays used throughout this script.
     *
     * @author Bion Oren
     * @version 1.0
     */
    class Main {
        /**
         *  INTEGER The maximum number of class dropdowns to display.
         *
         *  For those of you wondering why this number is so high, I know an aviation major
         *  taking 11 classes next semester.
         */
        const NUM_CLASSES = 20;
        /** ARRAY Mapping of semester abbreviations to long names. */
        public static $SEMESTER_NAMES = array("SP"=>"Spring", "SU"=>"Summer", "FA"=>"Fall");

        /** STRING The name of the campus we're getting courses for. */
        protected static $campus;
        /** ARRAY Array of the form classGroups[dept] = [select_option_block]. */
        protected $classGroups = array();
        /** STRING The unique name of the current semester. */
        protected static $semester;
        /** BOOLEAN True if we're dealing with traditional courses. */
        protected static $traditional;

        /** ARRAY Sorted array of the form classes[dept][classUID] = [Course]. */
        protected $classes = array();
        /** ARRAY Numeric array of course objects for the currently selected courses. */
        protected $courses = array();
        /** ARRAY Array of the form courseTitleNumbers[dept.num.section] = [Course]. */
        protected $courseTitleNumbers = array();
        /** ARRAY Array of error messages for classes keyed by the class' order of selection. */
        protected $errors = array();
        /** INTEGER The total number of hours for the selected classes. */
        protected $hours = 0;
        /** ARRAY Array of filters to keep classes - of the form keepFilter[classID] = [sectionNumber]. */
        protected $keepFilter = array();
        /** ARRAY Array of filters to remove classes - of the form removeFilter[classUID]*/
        protected $removeFilter = array();
        /** ARRAY Numeric array of generated schedules. */
        protected $schedules = null;
        /** BOOLEAN True if links to the bookstore website should be shown (which is slow). */
        protected $showBooks = false;
        /** BOOLEAN True if schedules should be generated. */
        protected $submit = false;

        /**
         * Initializes all the class variables.
         */
        public function __construct() {
            Main::$semester = $this->getCurrentSemester();
            Main::$traditional = !isset($_REQUEST["type"]) || $_REQUEST["type"] != "non";
            Main::$campus = (isset($_REQUEST["campus"]))?$_REQUEST["campus"]:"MAIN";
            $this->showBooks = isset($_REQUEST["showBooks"]) && $_REQUEST["showBooks"] == "on";
            $this->submit = isset($_REQUEST["submit"]);
            $this->keepFilter = $this->getChosenClasses();
            $this->removeFilter = $this->getRemovedClasses();

            //setup query string cache for courses
            Course::generateQS();
            $this->init();
        }

        /**
         * Displays the generated schedule(s) to the user with all the pretty and error
         * messages that may or may not go with that.
         *
         * @return VOID
         */
        public function displaySchedules() {
            foreach($this->keepFilter as $val) {
                print '<input type="hidden" name="cf[]" value="'.$val.'"/>';
            }

            if($this->isSubmitted() && $this->hasNoErrors() && count($this->getCourses()) > 0) {
                if($this->getSchedules()) {
                    print '<h2>Schedule</h2>';
                    Schedule::display($this->getSchedules());
                    print '<br/>';
                    print '<div style="text-align:center;">';
                        print '<img id="schedule" alt="Schedule" src="print.php?'.Schedule::getPrintQS(Schedule::$common).'" height="600"/>';
                        print '<br/>';
                    print '</div>';
                } else {
                    print "<span style='color:red;'>".$main->getSchedules()."</span>";
                }
            }
        }

        /**
         * Returns the campus classes are coming from.
         *
         * @return STRING
         * @see $campus
         */
        public static function getCampus() {
            return Main::$campus;
        }

        /**
         * Returns an array of the selected (via filter) classes.
         *
         * @return ARRAY Classes that should be selected.
         */
        protected function getChosenClasses() {
            $classFilter = array();
            if(isset($_REQUEST["cf"])) {
                foreach($_REQUEST["cf"] as $class) {
                    $classFilter[substr($class, 0, 9)] = substr($class, -2);
                }
            }
            return $classFilter;
        }

        /**
         * Returns the department of the ith selected class.
         *
         * @param INTEGER $i The number of the class to get.
         * @return STRING 4 letter department acronym.
         */
        protected function getClass($i) {
            return $_REQUEST["class"][$i];
        }

        /**
         * Returns the classID of the ith selected class.
         *
         * @param INTEGER $i The number of the class to get.
         * @return STRING ClassID.
         */
        protected function getClassChoice($i) {
            if(isset($_REQUEST["choice"][$i])) {
                return $_REQUEST["choice"][$i];
            }
        }

        /**
         * Returns an internal array of classes.
         *
         * @return ARRAY
         * @see $classes
         */
        protected function getClasses() {
            return $this->classes;
        }

        /**
         * Returns an internal array of classes.
         *
         * @return ARRAY
         * @see $classGroups
         */
        protected function getClassGroups() {
            return $this->classGroups;
        }

        /**
         * Returns the link to use to clear all active filters.
         *
         * @return STRING Current URL minus filter vars.
         */
        public function getClearFilterLink() {
            $clear = false;
            if($this->isSubmitted()) {
                $clear = $this."?semester=".Main::getSemester();
                for($i = 0; $i < Main::NUM_CLASSES; $i++) {
                    $choice = $this->getClassChoice($i);
                    if(!empty($choice)) {
                        $clear .= "&amp;class[]=".$this->getClass($i)."&amp;choice[]=".$choice;
                    } else {
                        $clear .= "&amp;class[]=0";
                    }
                }
                if(isset($_REQUEST["type"])) {
                    $clear .= "&amp;type=".$_REQUEST["type"];
                }
                $clear .= "&amp;campus=".$this->getCampus()."&amp;submit=Filter";
            }
            return $clear;
        }

        /**
         * Returns an internal array of classes.
         *
         * @return ARRAY
         * @see $courses
         */
        protected function getCourses() {
            return $this->courses;
        }

        /**
         * Returns an internal array of classes.
         *
         * @return ARRAY
         * @see $courseTitleNumbers
         */
        protected function getCourseTitleNumbers() {
            return $this->courseTitleNumbers;
        }

        /**
         * Returns the name of the current semester.
         *
         * @return STRING Semester identifier.
         */
        protected function getCurrentSemester() {
            if(empty($_REQUEST["sem"])) {
                $files = getFileArray();
                return $files[0];
            } else {
                return $_REQUEST["semester"];
            }
        }

        /**
         * Returns the number of hours being taken.
         *
         * @return INTEGER
         * @see $hours
         */
        public function getHours() {
            return $this->hours;
        }

        protected function getRemovedClasses() {
            $classFilter = array();
            if(isset($_REQUEST["rf"])) {
                $classFilter = array_fill_keys($_REQUEST["rf"], true);
            }
            return $classFilter;
        }

        /**
         * Returns all valid generated schedules.
         *
         * @return ARRAY Numeric array of schedules.
         */
        public function getSchedules() {
            return $this->schedules;
        }

        /**
         * Returns the name of the current semester.
         *
         * @return STRING Semester identifier.
         */
        public static function getSemester() {
            return Main::$semester;
        }

        /**
         * Returns true if the ith input class caused an error.
         *
         * @return BOOLEAN True on error.
         */
        protected function hasError($i) {
            return isset($this->errors[$i]);
        }

        /**
         * Returns true if there were no fatal errors generating schedules.
         *
         * @return BOOLEAN True if no errors.
         */
        protected function hasNoErrors() {
            return empty($errors);
        }

        /**
         * Initilizes internal class arrays. Also fetches all valid schedules for the given input.
         *
         * @return VOID
         */
        protected function init() {
            //generate select option values for display later
            $data = array_filter(getClassData(Main::getSemester(), Main::isTraditional()), create_function('Course $class', 'return $class->getCampus() == "'.$this->getCampus().'" || $class->isOnline();'));
            foreach($data as $class) {
                if(!$this->isKept($class) || $this->isRemoved($class)) {
                    continue;
                }
                $course = substr($class->getID(), 0, 4);
                $this->classGroups[$course] = '<option value="'.$course.'">'.$course.'</option>';
                $this->classes[$course][$class->getID()] = $class;
                $this->courseTitleNumbers[$class->getID()][] = $class;
            }
            $this->classGroups = implode("", $this->getClassGroups());
            //alphabetize the class list
            array_multisort($this->classes);

            if($this->isSubmitted() && isset($_REQUEST["choice"])) {
                //gather input data
                foreach($_REQUEST["choice"] as $key) {
                    if(isset($this->courseTitleNumbers[$key])) {
                        $this->courses[] = $this->courseTitleNumbers[$key];
                    } else {
                        $this->errors[$key] = true;
                    }
                }

                if($this->hasNoErrors()) {
                    //find possible schedules
                    $this->schedules = findSchedules($this->getCourses());
                }
            }
        }

        /**
         * Returns true if the given class is marked (by filters) to be kept for consideration in schedules.
         *
         * @param COURSE $class Class to evaluate.
         * @return BOOLEAN True if kept.
         */
        protected function isKept(Course $class) {
            return !isset($this->keepFilter[$class->getID()]) || $this->keepFilter[$class->getID()] == $class->getSection();
        }

        /**
         * Returns true if the given class is marked (by filters) to be removed from consideration in schedules.
         *
         * @param COURSE $class Class to evaluate.
         * @return BOOLEAN True if kept.
         */
        protected function isRemoved(Course $class) {
            return isset($this->removeFilter[$class->getUID()]);
        }

        /**
         * Returns true if schedules should be generated.
         *
         * @return BOOLEAN True if the class form was submitted.
         */
        public function isSubmitted() {
            return $this->submit;
        }

        /**
         * Returns true if traditional classes are being evaluated.
         *
         * @return BOOLEAN True if traditional classes are being used.
         */
        public static function isTraditional() {
            return main::$traditional;
        }

        /**
         * Displays dropdowns to select which classes to take.
         *
         * @return VOID
         */
        public function printClassDropdowns() {
            $classes = $this->getClasses();
            $ctn = $this->getCourseTitleNumbers();
            for($i=0; $i < Main::NUM_CLASSES; $i++) {
                $class = $this->getClass($i);;
                $choice = $this->getClassChoice($i);
                if(!empty($class)) {
                    $tmp = str_replace('>'.$class, ' selected="selected">'.$class, $this->getClassGroups());
                } else {
                    $tmp = $this->getClassGroups();
                }
                print '<div id="classChoice'.$i.'"';
                if(empty($choice)) {
                    print ' style="display:none;"';
                }
                print '>';
                print '<select name="class[]" onchange="selectChange(this, \'choice'.$i.'\');Element.show(\'classChoice'.($i+1).'\')">';
                    print '<option value="0">----</option>'.$tmp;
                print '</select>';
                print '<div id="choice'.$i.'" style="display:inline;">';
                    $populated = false;
                    if(!empty($choice)) {
                        print "<select name='choice[]'>";
                            foreach($classes[$class] as $key=>$course) {
                                print '<option value="'.$key.'"';
                                $invalid = false;
                                if($choice == $key) {
                                    print ' selected="selected"';
                                    $this->hours += substr($key, -1);
                                    $populated = $key;
                                } else {
                                    foreach($this->getSchedules() as $schedule) {
                                        foreach($ctn[$course->getID()] as $section) {
                                            $invalid = $schedule->validateClass(null, $section);
                                            if(empty($invalid)) {
                                                break 2;
                                            }
                                        }
                                    }
                                }
                                if(empty($invalid) || !$this->getSchedules()) {
                                    print '>'.htmlspecialchars_decode($course->getTitle());
                                } else {
                                    print ' style="color:rgb(177, 177, 177);">'.htmlspecialchars_decode(substr($invalid, 0, -4));
                                }
                                print '</option>';
                            }
                        print "</select>";
                    }
                    print '</div>';
                    if($populated !== false && $this->showBooks()) {
                        print '&nbsp;&nbsp;'.Course::displayBookStoreLink($populated);
                    }
                    if($this->hasError($i)) {
                        print '<span style="color:red;">Sorry, this class is not offered this semester</span>';
                    }
                print '</div>';
            }

            //show an extra empty department dropdown
            print '<script type="text/javascript">';
                print 'Element.show("classChoice'.(count($_REQUEST["choice"])+1).'");';
            print '</script>';
        }

        /**
         * Prints javascript that builds a hash table of classes to use in the class selection dropdowns.
         *
         * @return VOID
         */
        public function printHeaderJS() {
            print "var arrItems=new Hash();\n";
            foreach($this->getClasses() as $group=>$class) {
                print "var t=new Hash();\n";
                foreach($class as $id=>$course) {
                    $invalid = false;
                    foreach($this->getSchedules() as $schedule) {
                        $ctn = $this->getCourseTitleNumbers();
                        foreach($ctn[$course->getID()] as $section) {
                            $invalid = $schedule->validateClass(null, $section);
                            if(empty($invalid)) {
                                break 2;
                            }
                        }
                    }
                    print "t.set('".$id."',new Array('";
                    if(empty($invalid) || !$this->getSchedules()) {
                        print addslashes(htmlspecialchars_decode($course->getTitle()))."', true";
                    } else {
                        print addslashes(htmlspecialchars_decode(substr($invalid, 0, -4)))."', false";
                    }
                    print "));\n";
                }
                print "arrItems.set('".$group."',t);\n";
            }
        }

        /**
         * Prints options for a SELECT block with all available semesters.
         *
         * @return VOID
         */
        public static function printSemesterOptions() {
            foreach(getFileArray() as $key) {
                print '<option value="'.$key.'"';
                if(Main::getSemester() == $key) {
                    print " selected='selected'";
                }
                print '>'.Main::$SEMESTER_NAMES[substr($key, -2)].' '.substr($key, 0, 4).'</option>';
            }
        }

        /**
         * Returns true if links to the bookstore should be shown.
         *
         * @return BOOLEAN True to show links.
         */
        public function showBooks() {
            return $this->showBooks;
        }

        /**
         * Returns the full name (including path) for this script.
         *
         * @return STRING Fully-qualified PHP file path.
         */
        public function __toString() {
            return $_SERVER["PHP_SELF"];
        }
    }
?>