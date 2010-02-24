<?php
    class Schedule {
        protected static $ID = 1;
        public static $common = array();
        protected $id;
        protected $classes;

        public function __construct(array $classes) {
            $this->id = Schedule::$ID++;
            $this->classes = array_merge(Schedule::$common, $classes);
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
                                $ret .= $tmp."<br>";
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

        public function getID() {
            return dechex($this->id);
        }

        protected static function showTraditionalHeaders($common=false) {
            if($common) {
                ?>
                <th colspan="2">Class</th>
                <th>Prof</th>
                <th>Days</th>
                <th>Time</th>
                <th>Section</th>
                <th>Registered/Size</th>
                <?php
            } else {
                ?>
                <th style="width:10%;"></th>
                <th style="width:30%;" colspan="2">Class</th>
                <th style="width:10%;">Prof</th>
                <th style="width:10%;">Days</th>
                <th style="width:20%;">Time</th>
                <th style="width:10%;">Section</th>
                <th style="width:10%;">Registered/Size</th>
                <?php
            }
        }

        protected static function showNonTraditionalHeaders($common=false) {
            if($common) {
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
            } else {
                ?>
                <th style="width:7%;"></th>
                <th style="width:30%;" colspan="2">Class</th>
                <th style="width:10%;">Prof</th>
                <th style="width:12%;">Dates</th>
                <th style="width:10%;">Days</th>
                <th style="width:13%;">Time</th>
                <th style="width:6%;">Section</th>
                <th style="width:6%;">Campus</th>
                <th style="width:10%;">Registered/Size</th>
                <?php
            }
        }

        public function display($total) {
            $qs = Schedule::getPrintQS($this->classes);
            ?>
          <div class="line"></div>
          <table class="full border">
            <tr>
              <?php
                if(isTraditional()) {
                    Schedule::showTraditionalHeaders();
                } else {
                    Schedule::showNonTraditionalHeaders();
                }
              ?>
            </tr>
            <?php
            Course::generateQS();
            if(count($this->getClasses()) != count(Schedule::$common)) {
                foreach(array_diff($this->classes, Schedule::$common) as $class) {
                    $class->display($total, true);
                }
            } else {
                foreach($this->classes as $class) {
                    $class->display($total);
                }
            }
            ?></table>
            <div class="leftcol"><a href="print.php?<?php echo $qs?>" target="_new">Week View</a></div>
<!--          <div class="rightcol" style="text-align:right;"><label for="keep<?php echo $this->getID()?>">Remove this schedule:</label> <input type="checkbox" name="sf[]" value="<?php echo $this->getID()?>" id="keep<?php echo $this->getID()?>"></div>-->
            <?php
        }

        public static function displayCommon($total, array $optionClasses=null) {
            if(count(Schedule::$common) != 0):
                ?>
                <p>These are the only times you can take these classes:</p>
                <p><a href="print.php?<?php echo Schedule::getPrintQS(Schedule::$common)?>" target="_new">Week View</a></p>
                <table class="full border">
                  <tr>
                    <?php
                        if(isTraditional()) {
                            Schedule::showTraditionalHeaders(true);
                        } else {
                            Schedule::showNonTraditionalHeaders(true);
                        }
                    ?>
                  </tr>
                <?php
                    foreach(Schedule::$common as $class) {
                        print $class->display($total);
                    }

                    if(!empty($optionClasses)) {
                        print "<tr><td style='border-bottom-color:black;' colspan='7'>";
                        print "These classes have some options:";
                        print "</td></tr>";
                        Course::generateQS();
                    }
                    foreach($optionClasses as $key=>$sections) {
                        print "<tr onclick='".Schedule::createJSToggle($sections)."'><td>+ ".$key."</td><td colspan='6'>".$sections[0]->getTitle()."</td></tr>";
                        foreach($sections as $section) {
                            print $section->display($total, true, true);
                        }
                    }
                ?>
                </table>
			<?php
            endif;
        }

        protected static function createJSToggle($sections) {
            $ret = "";
            $state = "visible";
            foreach($sections as $section) {
                $ret .= '$("'.$section->getID().'").style.visibility = "'.$state.'";';
            }
            return $ret;
        }

        public static function getPrintQS($classes=null) {
            $ret = '';
            foreach($classes as $class) {
                $ret .= $class->getPrintQS()."&amp;";
            }
            $ret = substr($ret, 0, strlen($ret)-5);
            return $ret;
        }

        public function getClasses() {
            return $this->classes;
        }

        public function __toString() {
            return "Schedule object";
        }
    }
?>