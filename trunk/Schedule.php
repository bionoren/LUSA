<?php
    class Schedule {
        public static $common = array();
        protected $classes;
        protected $uniqueClasses;
        protected $valid = true;

        public function __construct(array $classes) {
            $this->classes = Schedule::$common;
            foreach($classes as $class) {
                $this->addClass($class, false);
            }
            $this->validate();
        }

        public function addClass(Course $class, $validate=true) {
            $this->classes[] = $class;
            $this->uniqueClasses[] = $class;
            if($class->getLab() != null) {
                $this->classes[] = $class->getLab();
                $this->uniqueClasses[] = $class->getLab();
            }
            if($validate) {
                return $this->isValid();
            }
            return true;
        }

        public function validate() {
            //eliminate schedules that have overlaps
            $this->isValid = array_reduce($this->classes, array("this", "validateClass"));
            //return all the conflicts together
            return $this->isValid();
        }

        public function validateClass(Course $class1) {
            //eliminate schedules that have overlaps
            //you can always take online classes
            if($class1->isOnline()) {
                return false;
            }
            //check this class against all the others
            $ret = false;
            foreach($this->classes as $class2) {
                if(isDayOverlap($class1, $class2) && isDateOverlap($class1, $class2)) {
                    $tmp = checkTimeConflict($class1, $class2);
                    if($tmp !== false) {
                        $ret .= $tmp."<br>";
                    }
                }
            }
            //return all the conflicts together
            return $ret;
        }

        protected static function getOptionClasses(array $schedules) {
            $classOptions = array();
            foreach($schedules as $schedule) {
                foreach($schedule->getUniqueClasses() as $class) {
                    if(!in_array($class, Schedule::$common)) {
                        $classOptions[substr($class, 0, -3)][$class->getSection()] = $class;
                    }
                }
            }
            return $classOptions;
        }

        public function isValid() {
            return $this->valid;
        }

        protected static function showTraditionalHeaders() {
            ?>
            <th colspan="2">Class</th>
            <th>Prof</th>
            <th>Days</th>
            <th>Time</th>
            <th>Section</th>
            <th>Registered/Size</th>
            <?php
        }

        protected static function showNonTraditionalHeaders() {
            ?>
            <th colspan="2">Class</th>
            <th>Prof</th>
            <th>Dates</th>
            <th>Days</th>
            <th>Time</th>
            <th>Section</th>
            <th>Campus</th>
            <th>Registered/Size</th>
            <?php
        }

        public static function display(array $schedules) {
            $optionClasses = Schedule::getOptionClasses($schedules);
            //this is slow, but it makes classes look pretty
            usort(Schedule::$common, "classSort");
            print '<table class="full border">';
                print '<tr>';
                    if(Main::isTraditional()) {
                        Schedule::showTraditionalHeaders();
                    } else {
                        Schedule::showNonTraditionalHeaders();
                    }
                print '</tr>';
                if(count(Schedule::$common) > 0) {
                    print '<tr>
                        <td style="border-bottom-color:black;" colspan="7">
                            These are the only times you can take these classes:
                        </td>
                    </tr>';
                }
                foreach(Schedule::$common as $class) {
                    print $class->display()."\n";
                }

                if(!empty($optionClasses)) {
                    print "<tr><td style='border-bottom-color:black;' colspan='7'>";
                    print "These classes have some options:";
                    print "</td></tr>";
                }
                foreach($optionClasses as $key=>$sections) {
                    print "<tr style='cursor:pointer;' onclick='".Schedule::createJSToggle($sections, $key)."'><td><span id='".$key."'>+</span> ".$key."</td><td colspan='6'>".current($sections)->getTitle()." (".count($sections).")</td></tr>\n";
                    foreach($sections as $section) {
                        print $section->display(true)."\n";
                    }
                }
            print '</table>';
            print '<br/>';
            print '<a href="print.php?'.Schedule::getPrintQS(Schedule::$common).'" id="printer">Printer Friendly</a>';
        }

        protected static function createJSToggle(array $sections, $key) {
            $ret = 'state = "visible";';
            $ret .= 'if($("'.current($sections)->getID().'").style.visibility == "visible") { state = "collapse"; }';
            $ret .= 'if(state == "visible") { $("'.$key.'").innerHTML = "-"; } else { $("'.$key.'").innerHTML = "+"; }';
            foreach($sections as $section) {
                $ret .= '$("'.$section->getID().'").style.visibility = state;';
            }
            return $ret;
        }

        public static function getPrintQS($classes=null) {
            $ret = '';
            foreach($classes as $class) {
                $ret .= $class->getPrintQS()."~";
            }
            $ret = substr($ret, 0, strlen($ret)-1);
            $ret = str_replace(" ", "%20", $ret);
            return $ret;
        }

        public function getClasses() {
            return $this->classes;
        }

        public function getUniqueClasses() {
            return $this->uniqueClasses;
        }

        public function __toString() {
            return "Schedule object";
        }
    }
?>