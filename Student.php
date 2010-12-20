<?php
    /**
     * Handles processing of the main page for studnet schedules.
     *
     * Provides user input to other classes that need it and holds intermediary
     * class information arrays used throughout this script.
     *
     * @author Bion Oren
     * @version 1.0
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
        /** ARRAY Array of the form courseTitleNumbers[dept.num.section] = [Course]. */
        protected $courseTitleNumbers = array();
        /** ARRAY List of department names. */
        protected $departments = array();
        /** ARRAY Array of error messages for classes keyed by the class' order of selection. */
        protected $errors = array();
        /** INTEGER The total number of hours for the selected classes. */
        protected $hours = 0;
        /** ARRAY Associative array of selected courses (DEPT####). */
        protected $selectedChoices = array();
        /** BOOLEAN True if links to the bookstore website should be shown (which is slow). */
        protected $showBooks = false;

        /**
         * Initializes all the class variables.
         */
        public function __construct() {
            parent::__construct();

            if(Main::haveSelections()) {
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
		 * Displays the body of the page (forms, output, etc).
		 *
		 * @return VOID
		 */
        public function display() {
            print '<div class="print-no">';
                print '<h2>Selected Classes</h2>';
                print '<div id="classDropdowns">';
                print '</div>';
                print '<span id="schedHours">'.$this->getHours().'</span> Credit Hours';
            print '</div>';
            print '<div id="schedule">';
                print '<h2>Schedule</h2>';
                //make classes show up in a pretty order
                print '<table class="full border">';
                    print '<thead>';
                        print '<tr>';
                            if(Main::isTraditional()) {
                                Student::showTraditionalHeaders();
                            } else {
                                Student::showNonTraditionalHeaders();
                            }
                        print '</tr>';
                    print '</thead>';
                    print '<tbody id="classes">';
                        $this->displaySchedules();
                    print '</tbody>';
                print '</table>';
                print '<br/>';
                print '<div style="text-align:center;">';
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
                    print '<img id="scheduleImg" alt="Schedule" src="print.php?'.Student::getPrintQS(Student::$common).$extra.'" height="100%"/>';
                    print '<br/>';
                print '</div>';
            print '</div>';
        }

        /**
         * Displays the generated schedule(s) to the user with all the pretty and error
         * messages that may or may not go with that.
         *
         * @return VOID
         */
        public function displaySchedules() {
            $span = (Main::isTraditional())?7:9;
            if(Main::haveSelections()) {
                $noCommon = true;
                $haveOthers = false;
                if(is_array($this->getCourses())) {
                    foreach($this->getCourses() as $sections) {
                        if(count($sections) == 1) {
                            if($noCommon) {
                                $noCommon = false;
                                print '<tr><td style="border-bottom-color:black;" colspan="'.$span.'">';
                                    print 'These are the only times you can take these classes:';
                                print '</td></tr>';
                            }
                            print $sections[0]->display()."\n";
                            Student::$common[] = $sections[0];
                        } else {
                            $haveOthers = true;
                        }
                    }

                    if($haveOthers) {
                        print '<tr><td style="border-bottom-color:black;" colspan="'.$span.'">';
                            print 'These classes have some options:';
                        print '</td></tr>';

                        foreach($this->getCourses() as $sections) {
                            if(count($sections) > 1) {
                                $key = current($sections)->getID();
                                print '<tr style="cursor:pointer;" class="'.$key.'" onclick="Course.toggle(\''.$key.'\');"><td><span id="'.$key.'">+</span> '.$key.'</td><td colspan="'.($span-1).'">'.current($sections)->getTitle().' ('.count($sections).')</td></tr>';
                                foreach($sections as $section) {
                                    print $section->display(true);
                                }
                            }
                        }
                    }
                } else {
                    print '<tr><td id="error" style="color:red;" colspan="'.$span.'">Conflicts were found :(<br>'.$this->getCourses().'</td></tr>';
                }
            }
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
         * Returns an internal array of classes.
         *
         * @return ARRAY
         * @see $classes
         */
        protected function getClasses() {
            return $this->classes;
        }

        /**
         * Returns a JSON list of class names for the given department.
         *
         * @param STRING $dept 4 letter department code.
         * @return STRING JSON encoded class list.
         */
        public function getCourseJSON($dept) {
            $classes = $this->getClasses();
            $ret = array();
            $tmp = array();
            foreach($classes[$dept] as $key=>$sections) {
                $tmp["class"] = $sections[0]->getLabel();
                $tmp["error"] = $this->checkValidClass($sections);
                $ret[$key] = $tmp;
            }
            return json_encode($ret);
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
         * Returns a JSON list of department names.
         *
         * @return STRING JSON encoded department list.
         */
        public function getDepartmentJSON() {
            return json_encode($this->getDepartments());
        }

        /**
         * Accessor for the internal departments array.
         *
         * @return STRING Department array.
         * @see $departments
         */
        protected function getDepartments() {
            return $this->departments;
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
         * Returns an array of the unique selected courses.
         *
         * @return ARRAY
         * @see $selectedChoices
         */
        protected function getSelectedChoices() {
            return $this->selectedChoices;
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
         * Returns true if the given class is marked (by filters) to be kept for consideration in schedules.
         *
         * @param COURSE $class Class to evaluate.
         * @return BOOLEAN True if kept.
         */
        public static function isKept(Course $class) {
            return !isset(Student::$keepFilter[$class->getID()]) || Student::$keepFilter[$class->getID()] == $class->getUID();
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
            $data = array_filter($classData, create_function('Course $class', 'return $class->getCampus() & "'.$this->campusMask.'";'));
            foreach($data as $class) {
                $dept = substr($class->getID(), 0, 4);
                $this->departments[$dept] = $dept;
                $this->classes[$dept][$class->getID()][] = $class;
                $this->courseTitleNumbers[$class->getID()][] = $class;
            }
            //alphabetize the class list
            array_multisort($this->classes);

            if(Main::haveSelections()) {
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
         * Returns true if links to the bookstore should be shown.
         *
         * @return BOOLEAN True to show links.
         */
        public function showBooks() {
            return $this->showBooks;
        }

        /**
         * Prints headers for non-traditional classes.
         *
         * @return VOID
         */
        protected static function showNonTraditionalHeaders() {
            ?>
            <th colspan="2" id="classHeader">Class</th>
			<th id="sectionHeader">Section</th>
			<th id="campusHeader">Campus</th>
            <th id="profHeader">Prof</th>
            <th id="dateHeader">Dates</th>
            <th id="dayHeader">Days</th>
            <th id="timeHeader">Time</th>
			<th id="registeredHeader">Registered/Size</th>
            <?php
        }

        /**
         * Prints headers for traditional classes.
         *
         * @return VOID
         */
        protected static function showTraditionalHeaders() {
            ?>
            <th colspan="2" id="classHeader">Class</th>
			<th id="sectionHeader">Section</th>
            <th id="profHeader">Prof</th>
            <th id="dayHeader">Days</th>
            <th id="timeHeader">Time</th>
			<th id="registeredHeader">Registered/Size</th>
            <?php
        }
    }
?>