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

    class Main {
        //for those of you wondering why this number is so high, I know an aviation major taking 11 classes next semester.
        const NUM_CLASSES = 20;
        public static $SEMESTER_NAMES = array("SP"=>"Spring", "SU"=>"Summer", "FA"=>"Fall");

        protected static $semester;
        protected static $traditional;
        protected static $campus;
        protected $classGroups = array();
        protected $classes = array();
        protected $courseTitleNumbers = array();
        protected $courses = array();
        protected $submit = false;
        protected $schedules = null;
        protected $showBooks = false;
        protected $errors = array();
        protected $hours = 0;
        protected $keepFilter = array();
        protected $removeFilter = array();

        public function __construct() {
            Main::$semester = $this->getCurrentSemester();
            Main::$traditional = !isset($_REQUEST["type"]) || $_REQUEST["type"] != "non";
            Main::$campus = (isset($_REQUEST["campus"]))?$_REQUEST["campus"]:"MAIN";
            $this->showBooks = isset($_REQUEST["showBooks"]) && $_REQUEST["showBooks"] == "on";
            $this->submit = isset($_REQUEST["submit"]);
            $this->keepFilter = $this->getChosenClasses();
            $this->removeFilter = $this->getRemovedClasses();

            $this->init();
        }

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

        public static function getCampus() {
            return Main::$campus;
        }

        protected function getChosenClasses() {
            $classFilter = array();
            if(isset($_REQUEST["cf"])) {
                foreach($_REQUEST["cf"] as $class) {
                    $classFilter[substr($class, 0, 9)] = substr($class, -2);
                }
            }
            return $classFilter;
        }

        protected function getClass($i) {
            return $_REQUEST["class"][$i];
        }

        protected function getClassChoice($i) {
            return $_REQUEST["choice"][$i];
        }

        protected function getClasses() {
            return $this->classes;
        }

        protected function getClassGroups() {
            return $this->classGroups;
        }

        public function getClearFilterLink() {
            $clear = false;
            if($this->isSubmitted()) {
                $clear = $main."?semester=".Main::getSemester();
                for($i = 0; $i < $NUM_CLASSES; $i++) {
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

        protected function getCourses() {
            return $this->courses;
        }

        protected function getCourseTitleNumbers() {
            return $this->courseTitleNumbers;
        }

        protected function getCurrentSemester() {
            if(empty($_REQUEST["sem"])) {
                $files = getFileArray();
                return $files[0];
            } else {
                return $_REQUEST["semester"];
            }
        }

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

        public function getSchedules() {
            return $this->schedules;
        }

        public static function getSemester() {
            return main::$semester;
        }

        protected function hasError($i) {
            return isset($this->errors[$i]);
        }

        protected function hasNoErrors() {
            return empty($errors);
        }

        protected function init() {
            //generate select option values for display later
            $data = getClassData(Main::getSemester(), Main::isTraditional(), Main::getCampus());
            foreach($data as $class) {
                if(!$this->isKept($class) || $this->isRemoved($class)) {
                    continue;
                }
                $course = substr($class->getCourseID(), 0, 4);
                $this->classGroups[$course] = '<option value="'.$course.'">'.$course.'</option>';
                $this->classes[$course][$class->getCourseID()] = $class;
                $this->courseTitleNumbers[$class->getCourseID()][] = $class;
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

        protected function isKept(Course $class) {
            return !isset($this->keepFilter[$class->getCourseID()]) || $this->keepFilter[$class->getCourseID()] == $class->getSection();
        }

        protected function isRemoved(Course $class) {
            return isset($this->removeFilter[$class->getID()]);
        }

        public function isSubmitted() {
            return $this->submit;
        }

        public static function isTraditional() {
            return main::$traditional;
        }

        public function printClassDropdowns() {
            $classes = $this->getClasses();
            $ctn = $this->getCourseTitleNumbers();
            for($i=0; $i < Main::NUM_CLASSES; $i++) {
                $class = $this->getClass($i);;
                $choice = $this->getClassChoice($i);
                if(!empty($class)) {
                    $tmp = str_replace('">', '" selected="selected">', $this->getClassGroups());
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
                                        foreach($ctn[$course->getCourseID()] as $section) {
                                            $invalid = $schedule->validateClass($section);
                                            if($invalid === false) {
                                                break 2;
                                            }
                                        }
                                    }
                                }
                                if($invalid === false || !$this->getSchedules()) {
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

        public function printHeaderJS() {
            print "var arrItems=new Hash();\n";
            foreach($this->getClasses() as $group=>$class) {
                print "var t=new Hash();\n";
                foreach($class as $id=>$course) {
                    $invalid = false;
                    foreach($this->getSchedules() as $schedule) {
                        $ctn = $this->getCourseTitleNumbers();
                        foreach($ctn[$course->getCourseID()] as $section) {
                            $invalid = $schedule->validateClass($section);
                            if($invalid === false) {
                                break 2;
                            }
                        }
                    }
                    print "t.set('".$id."',new Array('";
                    if($invalid === false || !$this->getSchedules()) {
                        print addslashes(htmlspecialchars_decode($course->getTitle()))."', true";
                    } else {
                        print addslashes(htmlspecialchars_decode(substr($invalid, 0, -4)))."', false";
                    }
                    print "));\n";
                }
                print "arrItems.set('".$group."',t);\n";
            }
        }

        public static function printSemesterOptions() {
            foreach(getFileArray() as $key) {
                print '<option value="'.$key.'"';
                if(Main::getSemester() == $key) {
                    print " selected='selected'";
                }
                print '>'.Main::$SEMESTER_NAMES[substr($key, -2)].' '.substr($key, 0, 4).'</option>';
            }
        }

        public function showBooks() {
            return $this->showBooks;
        }

        public function __toString() {
            return $_SERVER["PHP_SELF"];
        }
    }
?>