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

	require_once("Student.php");
	require_once("Professor.php");

    /**
     * Handles processing of the main page.
     *
     * Provides user input to other classes that need it and holds intermediary
     * class information arrays used throughout this script.
     *
     * @author Bion Oren
     * @version 1.0
     */
    abstract class Main {
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
		/** BOOLEAN True if student information should be evaluated and displayed. */
		protected static $student;
		/** BOOLEAN True if schedules should be generated. */
        protected static $submit = false;
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

        /**
         * Initializes all the class variables.
         */
        public function __construct() {
        }

        /**
         * Checks if the given course is valid in at least one available schedule.
         *
         * @param $sections ARRAY - List of sections (a section list is a list of Course objects).
         * @return MIXED False if no errors, error string otherwise.
         */
        function checkValidClass(array $sections) {
			$choices = $this->getSelectedChoices();
            if($this->hasNoErrors() && !isset($choices[$sections[0]->getID()])) {
                $courses = $this->getCourses();
				$courses[] = $sections;
				$conflict = $this->findSchedules($courses);
				if(is_array($conflict)) {
					return false;
				} else {
					return $conflict;
				}
            }
            return false;
        }

		/**
		 * Used to validate classes in a dropdown list
		 *
		 * @param $courses ARRAY List of sections.
		 * @return MIXED A conflict message if there was a conflict, null if there wasn't a conflict.
		 */
		function findSchedules(array $courses) {
			$numCourses = count($courses);
			$indexes = array_fill(0, $numCourses, 0);
			$classes = array();
			while(true) {
				for($i = 0; $i < $numCourses; $i++) {
					$classes[$i] = $courses[$i][$indexes[$i]];
				}
				if(isValidSchedule($classes)) {
					return null;
				}
				//for each course, if the index for this course is less than the max section index, shift it
				//also handles rollover for previous indicies
				for($i = 0; ++$indexes[$i] == count($courses[$i]);) {
					$indexes[$i++] = 0;
					//this exits the loop
					if($i == $numCourses) break 2;
				}
			}

			$conflict = findConflicts($courses, true);
			return implode("<br>", $conflict);
		}

		/**
		 * Displays the body of the page (forms, output, etc).
		 *
		 * @return VOID
		 */
		public abstract function display();

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
                foreach($this->getSelectedChoices() as $choice) {
                    $clear .= "&amp;class[]=".substr($choice, 0, 4)."&amp;choice[]=".$choice;
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
        protected static function getCurrentSemester() {
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

		/**
		 * Returns a list of classes that are removed by filters
		 *
		 * @return ARRAY List of removed class identifiers.
		 */
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
        protected static function haveSelections() {
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
		 * Sets up static environment variables.
		 *
		 * @return VOID
		 */
		public static function init() {
			Main::$semester = Main::getCurrentSemester();
            Main::$traditional = !isset($_REQUEST["type"]) || $_REQUEST["type"] != "non";
			Main::$student = !isset($_REQUEST["role"]) || $_REQUEST["role"] == "student";
            Main::$campus = (isset($_REQUEST["campus"]))?$_REQUEST["campus"]:"MAIN";
//            $this->showBooks = isset($_REQUEST["showBooks"]) && $_REQUEST["showBooks"] == "on";
            Main::$submit = isset($_REQUEST["submit"]);
		}

        /**
         * Returns true if the given class is marked (by filters) to be kept for consideration in schedules.
         *
         * @param $class COURSE - Class to evaluate.
         * @return BOOLEAN True if kept.
         */
        protected function isKept(Course $class) {
            return !isset($this->keepFilter[$class->getID()]) || $this->keepFilter[$class->getID()] == $class->getUID();
        }

        /**
         * Returns true if the given class is marked (by filters) to be removed from consideration in schedules.
         *
         * @param $class COURSE - Class to evaluate.
         * @return BOOLEAN True if kept.
         */
        protected function isRemoved(Course $class) {
            return isset($this->removeFilter[$class->getUID()]);
        }

		/**
		 * Returns true if the schedules to be displayed are for students.
		 *
		 * @return BOOLEAN True for student schedules.
		 */
		public static function isStudent() {
			return Main::$student;
		}

        /**
         * Returns true if schedules should be generated.
         *
         * @return BOOLEAN True if the class form was submitted.
         */
        public static function isSubmitted() {
            return Main::$submit;
        }

        /**
         * Returns true if traditional classes are being evaluated.
         *
         * @return BOOLEAN True if traditional classes are being used.
         */
        public static function isTraditional() {
            return Main::$traditional;
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
		 * Sets the bitmask for campuses that should be displayed.
		 *
		 * @return VOID
		 */
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