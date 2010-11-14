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
        /** ARRAY Array of filters to keep classes - of the form keepFilter[classID] = [sectionNumber]. */
        protected static $keepFilter = array();

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
            parent::__construct();

            //removes duplicate entries
            if(isset($_REQUEST["class"])) {
                $this->selectedClasses = array_filter($_REQUEST["class"]);
            }
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
		 * Displays the body of the page (forms, output, etc).
		 *
		 * @return VOID
		 */
        public function display() {
            print '<div class="print-no">';
                print '<h2>Selected Classes</h2>';
                $this->printClassDropdowns();
                print '<span id="schedHours">'.$this->getHours().'</span> Credit Hours';
            print '</div>';
            print '<div id="schedule">';
                $this->displaySchedules();
            print '</div>';
        }

        /**
         * Displays the generated schedule(s) to the user with all the pretty and error
         * messages that may or may not go with that.
         *
         * @return VOID
         */
        public function displaySchedules() {
            if($this->isSubmitted() && Main::haveSelections()) {
                if($this->hasNoErrors()) {
                    print '<h2>Schedule</h2>';
                    Schedule::display($this->getCourses());
                    print '<div style="text-align:center;">';
                        print '<img id="scheduleImg" alt="Schedule" src="print.php?'.Schedule::getPrintQS(Schedule::$common).'" height="880"/>';
                        print '<br/>';
                    print '</div>';
                } else {
                    print "<span style='color:red;'>Conflicts were found :(<br>".$this->getCourses()."</span>";
                }
            }
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
         * @see $classGroups
         */
        protected function getClassGroups() {
            return $this->classGroups;
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
         * Returns the number of hours being taken.
         *
         * @return INTEGER
         * @see $hours
         */
        public function getHours() {
            return $this->hours;
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
         * @param $class COURSE - Class to evaluate.
         * @return BOOLEAN True if kept.
         */
        public static function isKept(Course $class) {
            return !isset(Student::$keepFilter[$class->getID()]) || Student::$keepFilter[$class->getID()] == $class->getUID();
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
            print '<div id="classChoice'.$uid.'" class="classDD">';
                print '<select name="class[]" id="classDD'.$uid.'" onchange="departmentSelected(\''.$uid.'\', \''.Main::getSemester().'\')">';
                    print '<option value="0">----</option>'.$tmp;
                print '</select>';
                print '<label for="classDD'.$uid.'" style="display:none;">Class selection dropdown</label>';
                print '<div id="choice'.$uid.'" style="display:inline;">';
                    $populated = !empty($choice);
                    if($populated) {
                        print '<select name="choice[]" id="choiceDD'.$uid.'" class="choiceDD" onchange="courseSelected(this)" >';
                            print '<option value="0">----</option>';
                            foreach($classes[$class] as $key=>$sections) {
                                print '<option value="'.$key.'"';
                                if($choice == $key) {
                                    $this->hours += substr($key, -1);
                                    print ' selected="selected"';
                                }
	                            $error = $this->checkValidClass($sections);
                                if(!($error && $this->getCourses())) {
                                    print '>'.$sections[0]->getLabel();
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
         * Displays dropdowns to select which classes to take.
         *
         * @return VOID
         */
        public function printClassDropdowns() {
            print '<div id="classDropdowns">';
                if(Main::haveSelections()) {
                    foreach($this->getSelectedChoices() as $choice) {
                        $this->printClassDropdown(substr($choice, 0, 4), $choice);
                    }
                }

                //show an extra empty department dropdown
                $this->printClassDropdown();
            print '</div>';
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
                $course = substr($class->getID(), 0, 4);
                $this->classGroups[$course] = '<option value="'.$course.'">'.$course.'</option>';
                $this->classes[$course][$class->getID()][] = $class;
                $this->courseTitleNumbers[$class->getID()][] = $class;
            }
            $this->classGroups = implode("", $this->getClassGroups());
            //alphabetize the class list
            array_multisort($this->classes);

            if($this->isSubmitted() && Main::haveSelections()) {
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
    }
?>