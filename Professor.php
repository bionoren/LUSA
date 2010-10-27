<?php
    class Professor extends Main {
        protected $profClassList = array();
        protected $prof;

        /**
         * Initializes all the class variables.
         */
        public function __construct() {
            parent::__construct();

            Course::generateQS();
            if(isset($_REQUEST["prof"])) {
                $this->prof = $_REQUEST["prof"];
            }
            $this->generateProfList();
        }

        protected function generateProfList() {
            $classData = getClassData(Main::getSemester(), Main::isTraditional());
			Main::$CAMPUS_MASK = array_pop($classData);
			$this->setCampusMask();
			//generate select option values for display later
            $data = array_filter($classData, create_function('Course $class', 'return $class->getCampus() & "'.$this->campusMask.'";'));
            foreach($data as $class) {
                $list = $class->getProfClassList();
                foreach($list as $prof=>$class2) {
                    $this->profClassList[$prof][] = $class2;
                }
            }
            ksort($this->profClassList);
        }

        /**
		 * Displays the body of the page (forms, output, etc).
		 *
		 * @return VOID
		 */
        public function display() {
            print '<div class="print-no">';
                print 'Select a professor: ';
                $this->printProfDropdown($this->prof);
                print '<br/>';
                print '<input type="submit" name="submit" value="Show Schedule"/>';
            print '</div>';
            $this->displaySchedule();
        }

        /**
         * Displays the schedule for the selected professor.
         *
         * @return VOID
         */
        public function displaySchedule() {
            if($this->isSubmitted() && !empty($this->prof)) {
                print '<h2>Schedule</h2>';
                print '<div style="text-align:center;">';
                    print '<img id="schedule" alt="Schedule" src="print.php?'.Schedule::getPrintQS($this->profClassList[$this->prof]).'" height="600"/>';
                    print '<br/>';
                print '</div>';
            }
        }

        /**
         * Displays dropdown to select which prof to show a schedule for.
         *
         * @return VOID
         */
        public function printProfDropdown($choice=null) {
            print '<div id="profChoice">';
                print '<select name="prof" id="profDD">';
                    foreach($this->profClassList as $prof=>$classes) {
                        print '<option value="'.$prof.'"';
                        if($choice == $prof) {
                            print ' selected="selected"';
                        }
                        print '>'.$prof.'</option>';
                    }
                print '</select>';
                print '<label for="profDD" style="display:none;">Professor selection dropdown</label>';
                if($this->hasError($choice)) {
                    print '<span style="color:red;">Sorry, this prof isn\'t teaching anything at this campus this semester</span>';
                }
            print '</div>';
        }
    }
?>