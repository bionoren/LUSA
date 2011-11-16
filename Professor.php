<?php
    /**
     * Handles processing of the main professor schedule page.
     *
     * Provides user input to other classes that need it and holds intermediary
     * class information arrays used throughout this script.
     *
     * @author Bion Oren
     * @version 1.0
     */
    class Professor extends Main {
        /** ARRAY List of meetings times associated with a professor. $profClassList[PROF_NAME][] = Meeting */
        protected $profClassList = array();
        /** STRING The name of the currently selected professor. */
        protected $prof;

        /**
         * Initializes all the class variables.
         */
        public function __construct() {
            parent::__construct();

            if(isset($_REQUEST["prof"])) {
                $this->prof = $_REQUEST["prof"];
            }
            $this->generateProfList();
        }

        /**
         * Generates a sorted list of meetings associated with and keyed by the professor teaching them.
         *
         * @return ARRAY $ret[PROF_NAME][] = Meeting
         */
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
         * Displays dropdown to select which prof to show a schedule for.
         *
         * @return VOID
         */
        public function printProfDropdown($choice=null) {
            print '<div id="profChoice">';
                print '<select name="prof" id="profDD" onchange="profSelected(this)">';
                    print '<option value="0">----</option>';
                    foreach($this->profClassList as $prof=>$classes) {
                        print '<option value="'.$prof.'"';
                        if($choice == $prof) {
                            print ' selected="selected"';
                        }
                        print '>'.$prof.'</option>';
                    }
                print '</select>';
                print '<label for="profDD" style="display:none;">Professor selection dropdown</label>';
            print '</div>';
        }
    }
?>