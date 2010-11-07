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
        /**
         * Initializes all the class variables.
         */
        public function __construct() {
            parent::__construct();

            $this->keepFilter = $this->getChosenClasses();
            $this->removeFilter = $this->getRemovedClasses();
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

            //setup query string cache for courses
            Course::generateQS();
            $this->process();
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
                $i = 0;
                if(Main::haveSelections()) {
                    foreach($this->getSelectedChoices() as $choice) {
                        $this->printClassDropdown($i++, substr($choice, 0, 4), $choice);
                    }
                }

                //show an extra empty department dropdown
                $this->printClassDropdown($i++);
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
                if(!$this->isKept($class) || $this->isRemoved($class)) {
                    continue;
                }
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
    }
?>