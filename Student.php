<?php
    /**
     * Handles processing of the main page for student schedules.
     *
     * @author Bion Oren
     * @version 2.0
     */
    class Student extends Main {
        /** ARRAY List of common classes in the schedule. */
		public static $common = array();
        /** ARRAY Array of filters to keep classes - of the form keepFilter[classID] = [sectionNumber]. */
        protected static $keepFilter = array();

        /** ARRAY Sorted array of the form classes[dept][classID][] = [Course]. */
        protected $classes = array();
        /** MIXED Numeric array of course objects for the currently selected courses or an error string. */
        protected $courses = array();
        /** ARRAY List of department names. */
        protected $departments = array();
        /** ARRAY Array of error messages for classes keyed by the class' order of selection. */
        protected $errors = array();
        /** INTEGER The total number of hours for the selected classes. */
        protected $hours = 0;
        /** ARRAY Associative array of selected courses (DEPT####). */
        protected $selectedChoices = array();

        /**
         * Initializes all the class variables.
         */
        public function __construct() {
            parent::__construct();

            if(Main::haveSelections()) {
				if(!empty($_REQUEST["choice"]) && !is_array($_REQUEST["choice"])) {
					$_REQUEST["choice"] = array($_REQUEST["choice"]);
				}
				foreach($_REQUEST["choice"] as $course) {
					if(!empty($course)) {
						$this->selectedChoices[$course] = $course;
					}
				}
                if(empty($this->selectedChoices)) {
                    unset($_REQUEST["choice"]);
                }
            }

            $this->process();
        }

        /**
         * Checks if the given course is valid in at least one available schedule.
         *
         * @param ARRAY $sections List of sections (a section list is a list of Course objects).
         * @return MIXED False if no errors, error string otherwise.
         */
        function checkValidClass(array $sections) {
            if($this->hasNoErrors() && !isset($this->selectedChoices[$sections[0]->courseID])) {
				$courses = $this->courses;
				$courses[] = $sections;
				$conflict = $this->findSchedules($courses);
				if(is_array($conflict)) {
					return false;
				} else {
                    if($conflict) {
                        $ret = array();
                        foreach($sections as $section) {
                            $ret[] = $section->getPrintQS();
                        }
                        return implode("~", $ret);
                    } else {
                        return false;
                    }
				}
            }
            return false;
        }

        /**
		 * Used to validate classes in a dropdown list
		 *
		 * @param ARRAY $courses List of sections.
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
         * Returns an array of the selected (via filter) classes.
         *
         * @return ARRAY Classes that should be selected.
         */
        protected static function getChosenClasses() {
            $classFilter = array();
            if(isset($_REQUEST["cf"])) {
				foreach($_REQUEST["cf"] as $req) {
	                $classFilter[substr($req, 0, 9)] = $req;
				}
            }
            return $classFilter;
        }

        /**
         * Returns a JSON list of class names for the given department.
         *
         * @param STRING $dept 4 letter department code.
         * @return STRING JSON encoded class list.
         */
        public function getCourseJSON($dept) {
            $ret = array();
            $tmp = array();
            foreach($this->classes[$dept] as $key=>$sections) {
                $tmp["class"] = $sections[0]->getLabel();
                $tmp["error"] = $this->checkValidClass($sections);
                $ret[$key] = $tmp;
            }
            return json_encode($ret);
        }

        /**
         * Returns a JSON list of department names.
         *
         * @return STRING JSON encoded department list.
         */
        public function getDepartmentJSON() {
            return json_encode($this->departments);
        }

		public function getPrintExtra() {
			$extra = array();
			foreach(Student::$keepFilter as $uid) {
				$dept = substr($uid, 0, 4);
				$id = substr($uid, 0, 9);
				$extra[] = $this->classes[$dept][$id][array_search($uid, $this->classes[$dept][$id])]->getPrintQS();
			}
			$extra = implode("~", $extra);
			if(!empty($extra)) {
				$extra = "~".$extra;
			}
			return $extra;
		}

        /**
         * Returns the querystring used to generate a picture of the given classes.
         *
         * @param ARRAY $classes List of classes to display.
         * @return STRING Querystring for display.
         * @see print.php
         */
        public static function getPrintQS(array $classes) {
            $trad = (Main::isTraditional())?"trad":"non";
            $ret = 'sem='.Main::getSemester().'&trad='.$trad.'&classes=';
            $tmp = array();
            foreach($classes as $class) {
                $tmp[] = $class->getPrintQS();
            }
            $ret .= implode("~", $tmp);
            //return $ret;
            return htmlspecialchars($ret);
        }

        /**
         * Returns true if the given input class caused an error.
         *
         * @param STRING $index Class ID.
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
            return empty($errors) && is_array($this->courses);
        }

        /**
         * Returns true if the given class is marked (by filters) to be kept for consideration in schedules.
         *
         * @param COURSE $class Class to evaluate.
         * @return BOOLEAN True if kept.
         */
        public static function isKept(Course $class) {
            return !isset(Student::$keepFilter[$class->courseID]) || Student::$keepFilter[$class->courseID] == $class->uid;
        }

        /**
		 * Sets up static environment variables.
		 *
		 * @return VOID
		 */
        public static function init() {
            Student::$keepFilter = Student::getChosenClasses();
        }

        /**
         * Initilizes internal class arrays. Also fetches all valid schedules for the given input.
         *
         * @return VOID
         */
        protected function process() {
			$classData = getClassData(Main::getSemester(), Main::isTraditional());
			Main::$CAMPUS_MASK = array_pop($classData);
			$this->setCampusMask();
			//generate select option values for display later
            $data = array_filter($classData, function(Course $class) {
				return $class->getCampus() & $this->campusMask;
			});
			$courseTitleNumbers = array();
            foreach($data as $class) {
                $dept = substr($class->courseID, 0, 4);
                $this->departments[$dept] = $dept;
                $this->classes[$dept][$class->courseID][] = $class;
                $courseTitleNumbers[$class->courseID][] = $class;
            }
            //alphabetize the class list
            array_multisort($this->classes);

            if(Main::haveSelections()) {
                //gather input data
                foreach($this->selectedChoices as $key) {
                    $this->courses[] = $courseTitleNumbers[$key];
                }

				//find possible schedules
				$this->courses = findSchedules($this->courses);
				if(!is_array($this->courses)) {
					$this->errors = true;
				}

				foreach($this->courses as $sections) {
					if(count($sections) == 1) {
						Student::$common[] = $sections[0];
					}
				}
			}
        }
    }
?>