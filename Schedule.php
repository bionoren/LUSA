<?php
    class Schedule {
        public static $common = array();
        protected $classes;
        protected $uniqueClasses;

        public function __construct(array $classes) {
            $this->classes = array_merge(Schedule::$common, $classes);
            $this->uniqueClasses = $classes;
        }

        public function isValid() {
            //eliminate schedules that have overlaps
            $ret = "";
            for($i = 0; $i < count($this->classes)-1; $i++) {
                $class1 = $this->classes[$i];
                if($class1->isOnline()) {
                    continue;
                }
                for($j = $i+1; $j < count($this->classes); $j++) {
                    $class2 = $this->classes[$j];
                    if(isDateOverlap($class1, $class2)) {
                        if(isDayOverlap($class1, $class2)) {
                            $tmp = checkTimeConflict($class1, $class2);
                            if($tmp !== false) {
                                $ret .= $tmp."<br/>";
                            }
                        }
                    }
                    if(substr_compare($class1->getCourseID(), $class2->getCourseID(), 0, 9) == 0
                            && $class1->getSection() != $class2->getSection()) {
                        //if the course numbers are the same, but the sections don't match, fail
                        return false;
                    }
                }
            }
            //return all the conflicts together
            if(!empty($ret)) {
                return $ret;
            }
            //this is slower than above, but it makes them look pretty
            usort($this->classes, "classSort");
            return true;
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

        public static function displayCommon(array $optionClasses=null) {
            print '<table class="full border">';
                print '<tr>';
                    if(isTraditional()) {
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
                    print $class->display();
                }

                if(!empty($optionClasses)) {
                    print "<tr><td style='border-bottom-color:black;' colspan='7'>";
                    print "These classes have some options:";
                    print "</td></tr>";
                }
                foreach($optionClasses as $key=>$sections) {
                    print "<tr style='cursor:pointer;' onclick='".Schedule::createJSToggle($sections, $key)."'><td><span id='".$key."'>+</span> ".$key."</td><td colspan='6'>".current($sections)->getTitle()."</td></tr>";
                    foreach($sections as $section) {
                        print $section->display(true);
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