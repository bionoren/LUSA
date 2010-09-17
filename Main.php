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

    require_once("Course.php");
    require_once("Schedule.php");
    require_once("functions.php");

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
        /** ARRAY Mapping of semester abbreviations to long names. */
        public static $SEMESTER_NAMES = array("SP"=>"Spring", "SU"=>"Summer", "FA"=>"Fall");
		/** ARRAY Mapping from campus names to their bit value (for bitmasking campuses). */
		public static $CAMPUS_MASK = 0;

        /** STRING The name of the campus we're getting courses for. */
        protected static $campus;
        /** ARRAY Array of the form classGroups[dept] = [select_option_block]. */
        protected $classGroups = array();
        /** STRING The unique name of the current semester. */
        protected static $semester;
        /** BOOLEAN True if we're dealing with traditional courses. */
        protected static $traditional;

		/** INTEGER Mask of valid campuses to display. */
		protected $campusMask = 0;
        /** ARRAY Sorted array of the form classes[dept][classUID] = [Course]. */
        protected $classes = array();
        /** MIXED Numeric array of course objects for the currently selected courses or an error string. */
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
        /** ARRAY Associative array of selected classes (DEPT). */
        protected $selectedClasses = array();
        /** ARRAY Associative array of selected courses (DEPT####). */
        protected $selectedChoices = array();
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
            //removes duplicate entries
            if($this->haveSelections()) {
                $this->selectedClasses = array_filter($_REQUEST["class"]);
                foreach($_REQUEST["choice"] as $course) {
					if($course != "----") {
	                    $this->selectedChoices[$course] = $course;
					}
                }
            }

            //setup query string cache for courses
            Course::generateQS();
            $this->init();
        }

        /**
         * Checks if the given course is valid in at least one available schedule.
         *
         * @param COURSE $course Course object.
         * @return MIXED False if no errors, error string otherwise.
         */
        function checkValidClass(Course $course) {
			$choices = $this->getSelectedChoices();
            if($this->hasNoErrors() && !isset($choices[$course->getID()])) {
                foreach($this->getCourses() as $sections) {
					foreach($sections as $section) {
						$valid = $section->validateClasses($course);
						if($valid) {
							$tmp = null;
							break;
						} else {
							$tmp = $course->getLabel()." (conflicts with ".$section->getTitle().")";
						}
					}
					if($tmp) {
						return $tmp;
					}
				}
            }
            return false;
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

            if($this->isSubmitted() && $this->haveSelections()) {
                if($this->hasNoErrors()) {
                    print '<h2>Schedule</h2>';
                    Schedule::display($this->getCourses());
                    print '<br/>';
                    print '<div style="text-align:center;">';
                        print '<img id="schedule" alt="Schedule" src="print.php?'.Schedule::getPrintQS(Schedule::$common).'" height="600"/>';
                        print '<br/>';
                    print '</div>';
                } else {
                    print "<span style='color:red;'>Conflicts were found :(<br>".$this->getCourses()."</span>";
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
				foreach($_REQUEST["cf"] as $req) {
	                $classFilter[substr($req, 0, 9)] = $req;
				}
            }
            return $classFilter;
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
                reset($this->selectedClasses);
                foreach($this->getSelectedChoices() as $choice) {
                    $clear .= "&amp;class[]=".current($this->selectedClasses)."&amp;choice[]=".$choice;
                    next($this->selectedClasses);
                }
                if(isset($_REQUEST["type"])) {
                    $clear .= "&amp;type=".$_REQUEST["type"];
                }
                $clear .= "&amp;campus=".$this->getCampus()."&amp;submit=Filter";
            }
            return $clear;
        }

        /**
         * Returns the name of the cookie that should store previous schedule information.
         *
         * @return STRING Cookie name.
         */
        public static function getCookieName() {
            return Main::getSemester().Main::isTraditional().Main::getCampus();
        }

        /**
         * Returns an internal array of classes.
         *
         * @return MIXED
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
            if(empty($_REQUEST["semester"])) {
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
         * Returns an array of the unique selected courses.
         *
         * @return ARRAY
         * @see $selectedChoices
         */
        protected function getSelectedChoices() {
            return $this->selectedChoices;
        }

        /**
         * Returns an array of the unique selected classes.
         *
         * @return ARRAY
         * @see $selectedClasses
         */
        protected function getSelectedClasses() {
            return $this->selectedClasses;
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
         * Returns true if the user has selected at least one class.
         *
         * @return BOOLEAN True on user selection.
         */
        protected function haveSelections() {
            return isset($_REQUEST["choice"]);
        }

        /**
         * Returns true if the given input class caused an error.
         *
         * @return BOOLEAN True on error.
         */
        protected function hasError($index) {
            return isset($this->errors[$index]);
        }

        /**
         * Returns true if there were no fatal errors generating schedules.
         *
         * @return BOOLEAN True if no errors.
         */
        protected function hasNoErrors() {
            return empty($errors) && is_array($this->getCourses());
        }

        /**
         * Initilizes internal class arrays. Also fetches all valid schedules for the given input.
         *
         * @return VOID
         */
        protected function init() {
			$classData = getClassData(Main::getSemester(), Main::isTraditional());
			Main::$CAMPUS_MASK = array_pop($classData);
			$this->setCampusMask();
			//generate select option values for display later
            $data = array_filter($classData, create_function('Course $class', 'return $class->getCampus() & "'.$this->campusMask.'";'));
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

            if($this->isSubmitted() && $this->haveSelections()) {
                //gather input data
                foreach($this->getSelectedChoices() as $key) {
                    if(isset($this->courseTitleNumbers[$key])) {
                        $this->courses[] = $this->courseTitleNumbers[$key];
                    } else {
                        $this->errors[$this->courseTitleNumbers[$key]->getID()] = true;
                    }
                }

                if($this->hasNoErrors()) {
                    //find possible schedules
					$this->courses = findSchedules($this->getCourses());
                    if(!is_array($this->getCourses())) {
                        $this->errors = true;
                    }
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
            return !isset($this->keepFilter[$class->getID()]) || $this->keepFilter[$class->getID()] == $class->getUID();
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
            print '<div id="classDropdowns">';
                if($this->haveSelections()) {
                    reset($this->selectedClasses);
                    foreach($this->getSelectedChoices() as $choice) {
                        $this->printClassDropdown(current($this->selectedClasses), $choice);
                        next($this->selectedClasses);
                    }
                }

                //show an extra empty department dropdown
                $this->printClassDropdown();
            print '</div>';
        }

        /**
         * Displays dropdown to select which class to take.
         *
         * @return VOID
         */
        public function printClassDropdown($class=null, $choice=null) {
            $uid = md5(microtime());
            $classes = $this->getClasses();
            $ctn = $this->getCourseTitleNumbers();
            if(!empty($class)) {
                $tmp = str_replace('>'.$class, ' selected="selected">'.$class, $this->getClassGroups());
            } else {
                $tmp = $this->getClassGroups();
            }
            print '<div id="classChoice'.$uid.'">';
                print '<select name="class[]" id="classDD'.$uid.'" onchange="if($(\'choice'.$uid.'\').empty()){new Ajax.Updater(\'classDropdowns\',\'createClassDropdown.php\', {parameters: { semester:\''.Main::getSemester().'\'}, insertion: \'bottom\'});}selectChange(this, \'choice'.$uid.'\');">';
                    print '<option value="0">----</option>'.$tmp;
                print '</select>';
                print '<label for="classDD'.$uid.'" style="display:none;">Class selection dropdown</label>';
                print '<div id="choice'.$uid.'" style="display:inline;">';
                    $populated = !empty($choice);
                    if($populated) {
                        print '<select name="choice[]" id="choiceDD'.$uid.'">';
                            foreach($classes[$class] as $key=>$course) {
                                print '<option value="'.$key.'"';
                                if($choice == $key) {
                                    $this->hours += substr($key, -1);
                                    print ' selected="selected"';
                                }
	                            $error = $this->checkValidClass($course);
                                if(!($error && $this->getCourses())) {
                                    print '>'.$course->getLabel();
                                } else {
                                    print ' style="color:rgb(177, 177, 177);">'.$error;
                                }
                                print '</option>';
                            }
                        print "</select>";
                        print '<label for="choiceDD'.$uid.'" style="display:none;">Class selection dropdown</label>';
                    }
                print '</div>';
                if($populated && $this->showBooks()) {
                    print '&nbsp;&nbsp;'.Course::displayBookStoreLink($populated);
                }
                if($this->hasError($choice)) {
                    print '<span style="color:red;">Sorry, this class is not offered this semester</span>';
                }
            print '</div>';
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
					$error = $this->checkValidClass($course);
                    print "t.set('".$id."',new Array('";
                    if(!($error && $this->getCourses())) {
                        print htmlspecialchars_decode(addslashes($course->getLabel()))."', true";
                    } else {
                        print htmlspecialchars_decode(addslashes($error))."', false";
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

		public function setCampusMask() {
			$this->campusMask = Main::$CAMPUS_MASK[Main::$campus];
			if(Main::$campus == "MAIN" && isset(Main::$CAMPUS_MASK["ARPT"])) {
				$this->campusMask |= Main::$CAMPUS_MASK["ARPT"];
			}
			if(isset(Main::$CAMPUS_MASK["XOL"])) {
				$this->campusMask |= Main::$CAMPUS_MASK["XOL"];
			}
			if(isset(Main::$CAMPUS_MASK["N/A"])) {
				$this->campusMask |= Main::$CAMPUS_MASK["N/A"];
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