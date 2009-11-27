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


    //WARNING
    //This does not handle online classes. Basically, they always conflict with everything, because they are interpreted as
    //lasting all day, every day, forever and ever amen.

	//DEBUGGING FUNCTIONS
	function dump($name, $array, $member=null) {
		if(!is_array($array)) {
			print "$name = $array<br>";
		} else {
			foreach($array as $key=>$val) {
				if(is_array($val))
					dump($name."[$key]", $val, $member);
				else {
                    if($member == null) {
    					print $name."[".$key."] = ".$val."<br>";
                    } else {
                        print $name."[".$key."] = ".$val->{$member}()."<br>";
                    }
                }
			}
		}
	}

	//FUNCTIONS
    function save_cookie($data) {
        //set for ~2 months
        setcookie("lastSchedule", $data, time()+60*60*24*7*8);
    }

    function getCurrentSemester($year=null, $semester=null) {
        //get the current class schedule from LeTourneau
		if(!file_exists($year.$semester.".txt")) {
            //send the user back after 5 seconds
            print '<script language="javascript">setTimeout("history.back()",5000);</script>';
            die("There is no data available for $semester $year");
        }
        $file = fopen($year.$semester.".txt", "r");
        $title = fgets($file);
        fclose($file);
        return $title;
    }

    function getFileArray($reject=true) {
        //rollover on May 1st, August 1st, and January 1st
        $year = date("Y");
        $month = date("n");
        $day = date("j");
        $files = array();
        //order is important here!
        if($month < 5) {
            //this spring and try for this summer and fall
            if(!$reject || file_exists($year."FA.txt"))
                $files[] = array($year, "FA");
            if(!$reject || file_exists($year."SU.txt"))
                $files[] = array($year, "SU");
            $files[] = array($year, "SP");
        } elseif($month < 8) {
            //grab this summer and try for next fall
            if(!$reject || file_exists($year."FA.txt"))
                $files[] = array($year, "FA");
            $files[] = array($year, "SU");
        } else {
            //grab this fall and try for next spring
            if(!$reject || file_exists(($year+1)."SP.txt"))
                $files[] = array($year+1, "SP");
            $files[] = array($year, "FA");
        }
        return $files;
    }

	function getClassData($year, $semester) {
		if(!file_exists($year.$semester.".txt")) {
            die("There is no data available for $semester $year");
        }
        $file = fopen($year.$semester.".txt", "r");
        $classes = array();
        fgets($file); //burn the title
        while(!feof($file)) {
            $classes[] = new Course(array_combine(Course::$EXPORT, explode("$$", fgets($file))));
        }
        fclose($file);
        return $classes;
	}

    //filters the master class list down to the courses we're interested in and organizes the data into something parsable by evaluateSchedules()
    //just as a warning, this method took a lot of thought, but it really does work. Good luck...
	function findSchedules(array $courses, $filters=null) {
        if($filters !== null && !empty($filters)) {
            $filters = array_flip($filters);
        } else {
            $filters = null;
        }

        //add course information for all the courses to be taken
        //classes with only one section must be common
        foreach($courses as $i=>$sections) {
            if(count($sections) == 1) {
                Schedule::$common[] = $sections[0];
                unset($courses[$i]);
            }
        }
        if(count($courses) == 0) {
            $temp = new Schedule(array());
            $valid = $temp->isValid();
            if($valid === true) {
                return array();
            } else {
                return $valid;
            }
        }
        //fix the indices unset messed up
        $courses = array_values($courses);

        $indexes = array_fill(0, count($courses), 0);
        $schedules = array();
        $conflict = null;
        while(true) {
            $classes = array();
            foreach($courses as $i=>$sections) {
                $class = $sections[$indexes[$i]];
                $classes[] = $class;
            }
            //for each course, if the index for this course is less than the max section index, shift it
            //also handles rollover for previous indicies
            $temp = new Schedule($classes);
            if($filters === null || !array_key_exists($temp->getID(), $filters)) {
                $valid = $temp->isValid();
                if($valid === true) {
                    $schedules[] = $temp;
                } else {
                    $conflict = $valid;
                }
            }
            for($i = 0; ++$indexes[$i] == count($courses[$i]);) {
                $indexes[$i++] = 0;
                //this exits the loop
                if($i == count($courses)) break 2;
            }
        }
        if(count($schedules) == 0) {
            return $conflict;
        }
        //die(dump("filters", $filters));

        //find classes that could have had options, but only one works
        $common = array_diff($schedules[0]->getClasses(), Schedule::$common);
        foreach($schedules as $schedule) {
            $common = array_intersect($common, $schedule->getClasses());
            if(empty($common)) {
                break;
            }
        }
        Schedule::$common = array_merge(Schedule::$common, $common);
		return $schedules;
	}

	function classSort(Course $class1, Course $class2) {
        //if the classes aren't even on the same days, sort by days
		if(!isDayOverlap($class1, $class2)) {
			return daySort($class1, $class2);
		}
        return timeSort($class1, $class2);
	}

    function timeSort(Course $class1, Course $class2) {
        $start1 = $class1->getStartTime();
        $start2 = $class2->getStartTime();
        //returns -1 if class1 is before class2
        return ($start1 - $start2)*10; //return value needs to be +- 1. Otherwise, interpreted as 0
    }

	function daySort(Course $class1, Course $class2) {
		return $class2->getDays() - $class1->getDays();
	}

	function checkTimeConflict(Course $class1, Course $class2) {
		$start1 = $class1->getStartTime();
		$start2 = $class2->getStartTime();
		$end1 = $class1->getEndTime();
		$end2 = $class2->getEndTime();
        //if one of the classes ends before the other one starts, no overlap
        if($end1 < $start2 || $end2 < $start1) {
            return false;
        } else {
            return $class1->getTitle()." conflicts with ".$class2->getTitle();
        }
	}

	function isDayOverlap(Course $class1, Course $class2) {
        return ((int)$class1->getDays() & (int)$class2->getDays()) > 0;
	}

	function displaySchedules($schedules, $total) {
		if(is_array($schedules)):?>
            <br>
			<table class="full border">
				<?php if($total != count($schedules)):?>
					<tr><td>Showing <?php echo count($schedules)?> of <?php $total?> possible ways to take your other classes</td></tr>
				<?php else:?>
    				<tr><td>There are <?php echo count($schedules)?> possible ways to take the rest of your classes</td></tr>
				<?php endif;?>
				<?php foreach($schedules as $schedule):?>
					<tr><td style="border:0px;">
                        <?php $schedule->display($total)?>
                    </td></tr>
				<?php endforeach;?>
			</table>
		<?php else:?>
			<font color="red">Sorry, <?php echo $schedules ?></font><br>
		<?php endif;
	}

    class Schedule {
        protected static $ID = 1;
        public static $common = array();
        protected $id;
        protected $classes;

        public function __construct(array $classes) {
            $this->id = Schedule::$ID++;
            foreach($classes as $class) {
                $this->addClass($class);
            }
            foreach(Schedule::$common as $class) {
                $this->addClass($class);
            }
        }

        protected function addClass(Course $class) {
            $this->classes[] = $class;
        }

        public function isValid() {
            //eliminate schedules that have overlaps
            $ret = "";
            for($i = 0; $i < count($this->classes)-1; $i++) {
                $class1 = $this->classes[$i];
                for($j = $i+1; $j < count($this->classes); $j++) {
                    $class2 = $this->classes[$j];
                    if(isDayOverlap($class1, $class2)) {
                        $tmp = checkTimeConflict($class1, $class2);
                        if($tmp !== false) {
                            $ret .= $tmp."<br>";
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

        public function display($total) {
            $qs = Schedule::getPrintQS($this->classes);
            ?>
          <div class="line"></div>
          <table class="full border">
            <tr>
              <th style="width:10%;"></th>
              <th style="width:30%;" colspan="2">Class</th>
              <th style="width:10%;">Prof</th>
              <th style="width:10%;">Days</th>
              <th style="width:20%;">Time</th>
              <th style="width:10%;">Section</th>
              <th style="width:10%;">Registered/Size</th>
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
          <div class="rightcol" style="text-align:right;"><label for="keep<?php echo $this->getID()?>">Remove this schedule:</label> <input type="checkbox" name="sf[]" value="<?php echo $this->getID()?>" id="keep<?php echo $this->getID()?>"></div>
            <?php
        }

        public static function displayCommon($total) {
            if(count(Schedule::$common) != 0):
                ?>
                <p>These are the only times you can take these classes:</p>
                <p><a href="print.php?<?php echo Schedule::getPrintQS(Schedule::$common)?>" target="_new">Week View</a></p>
                <table class="full border">
                  <tr>
                    <th colspan="2">Class</th>
                    <th>Prof</th>
                    <th>Days</th>
                    <th>Time</th>
                    <th>Section</th>
                    <th>Registration/Size</th>
                  </tr>
                <?php
                foreach(Schedule::$common as $class) {
                    print $class->display($total);
                }
                ?>
                </table>
			<?php
            endif;
        }

        public static function getPrintQS($classes=null) {
            $ret = '';
            foreach($classes as $class) {
                $ret .= base64_encode(serialize($class))."&amp;";
            }
            return substr($ret, 0, strlen($ret)-1);
        }

        public function getClasses() {
            return $this->classes;
        }

        public function __toString() {
            return "Schedule object";
        }
    }

    class Course {
        protected static $ID = 1;
        //diff - Crs Start, Crs End, Campus
        //non-traditional keys
        public static $NON_KEYS = array("Course", "Sec", "Title", "Crs Start", "Crs End", "Professor", "Max Reg", "Cur Reg", "Type", "Days", "Times", "Campus", "Bldg", "Room");
        //traditional keys
        public static $KEYS = array("ref#", "course", "section", "title", "prof", "maxReg", "curReg", "type", "days", "times", "bldg", "room");
        public static $EXPORT = array("course", "section", "days", "start", "end", "title", "prof", "curReg", "maxReg");
        public static $QS = "";

        protected $id;
        protected $courseID;
        protected $section;
        protected $days;
        protected $startTime;
        protected $endTime;
        protected $title;
        protected $prof;
        protected $currentRegistered;
        protected $maxRegisterable;

        public function __construct(array $dataArray) {
            $this->id = Course::$ID++;
            $this->courseID = $dataArray["course"];
            $this->section = $dataArray["section"];
            $this->days = $dataArray["days"];
            if(array_key_exists("start", $dataArray)) {
                $this->startTime = $dataArray["start"];
                $this->endTime = $dataArray["end"];
            } else {
                $this->startTime = $this->convertTime($dataArray["times"][0]);
                $this->endTime = $this->convertTime($dataArray["times"][1]);
            }
            $this->title = $dataArray["title"];
            $this->prof = $dataArray["prof"];
            $this->currentRegistered = $dataArray["curReg"];
            $this->maxRegisterable = $dataArray["maxReg"];
            if(empty($this->currentRegistered)) {
                $this->currentRegistered = 0;
            }
        }

        protected function convertTime($timestr) {
            if($timestr == "TBA")
                return $timestr;
            $end = strlen($timestr)-1;
            //strip off the last character (a or p)
            $ap = substr($timestr, $end);
            //split minutes and hours
            $time = explode(":", substr($timestr, 0, $end));
            //convert to 24 hour format
            if($ap == "p")
                $time[0] = $time[0]%12 + 12;
            //convert minutes into a decimal
            return $time[0]+$time[1]/60;
        }

        public function displayTime($time) {
            if($time == "TBA")
                return $time;
            //separate hours and minutes
            $time = explode(".", $time);
            //if hours >= 12, then pm
            $ap = ($time[0]/12 >= 1)?"p":"a";
            //if hours > 12, then put back into 12 hour format
            if($time[0] > 12)
                $time[0] -= 12;
            //make the minutes a decimal number again
            $time[1] = ".".$time[1];
            //convert the decimal back to minutes
            $time[1] = round($time[1]*60);
            //add a leading zero if 0-9 minutes
            if($time[1] < 10)
                $time[1] = "0".$time[1];
            //return the time
            return $time[0].":".$time[1].$ap;
        }

        //fills in the mising data of this lab with the given class information
        public function mergeLabWithClass(Course $class) {
            if(empty($this->courseID))
                $this->courseID = $class->getCourseID()." lab";
            if(empty($this->section))
                $this->section = $class->getSection();
            if(empty($this->days))
                $this->days = $class->getDays();
            if(empty($this->startTime))
                $this->startTime = $class->getStartTime();
            if(empty($this->endTime))
                $this->endTime = $class->getEndTime();
            if(empty($this->title))
                $this->title = $class->getTitle()." lab";
            if(empty($this->prof))
                $this->prof = $class->getProf();
            if(empty($this->currentRegistered))
                $this->currentRegistered = $class->getCurrentRegistered();
            if(empty($this->maxRegisterable))
                $this->maxRegisterable = $class->getMaxRegistered();
        }

        public function getCourseID() {
            return $this->courseID;
        }

        public function getSection() {
            return $this->section;
        }

        public function getDays() {
            return $this->days;
        }

        public function getStartTime() {
            return $this->startTime;
        }

        public function getEndTime() {
            return $this->endTime;
        }

        public function getTitle() {
            return $this->title;
        }

        public function getProf() {
            return $this->prof;
        }

        public function getCurrentRegistered() {
            return $this->currentRegistered;
        }

        public function getMaxRegistered() {
            return $this->maxRegisterable;
        }

        public function display($total, $filterable=false) {
            //>5 seats left
            if($this->getMaxRegistered()-$this->getCurrentRegistered() > 5) {
                $status = 'status-open';
            } elseif($this->getMaxRegistered()-$this->getCurrentRegistered() <= 5 && (int)$this->getMaxRegistered() > (int)$this->getCurrentRegistered()) {
            //<5 seats left
                $status = 'status-close';
            } else {
            //no seats left
                $status = 'status-full';
            }
            print '<tr class="'.$status.'">';
                if($filterable) {
                    $qstring = Course::$QS.'cf[]='.$this->getID().'&amp;submit=Filter&amp;total='.$total;
                    print '<td><a href="'.$qstring.'" style="color:red; text-decoration:none;">Remove</a></td>';
                }
                print '<td>'.$this->getCourseID().'</td>';
                print '<td>'.$this->getTitle().'</td>';
                print '<td>'.$this->getProf().'</td>';
                print '<td>'.$this->dayString().'</td>';
                print '<td>'.$this->displayTime($this->getStartTime()).'-'.$this->displayTime($this->getEndTime()).'</td>';
                print '<td>'.$this->getSection().'</td>';
                print '<td>'.$this->getCurrentRegistered().'/'.$this->getMaxRegistered().'</td>';
            print '</tr>';
            return $ret;
        }

        function dayString() {
            $temp = array("S", "M", "T", "W", "R", "F", "S");
            $nums = array(1, 2, 4, 8, 16, 32, 64);
            $ret = "";
            for($i = 0; $i < count($temp); $i++) {
                if($this->getDays() & $nums[$i]) {
                    $ret .= $temp[$i];
                } else {
                    $ret .= "-";
                }
            }
            return $ret;
        }

        public function isEmpty() {
            return $this->getDays() > 0;
        }

        public function export() {
            return array($this->courseID, $this->section, $this->days, $this->startTime, $this->endTime, $this->title,
                $this->prof, $this->currentRegistered, $this->maxRegisterable);
        }

        public function getID() {
            return dechex($this->id);
        }

        public function __toString() {
            return "Course ".$this->getID();
        }

        public static function displayBookStoreLink($classID) {
            $terms = file_get_contents("http://www.bkstr.com/webapp/wcs/stores/servlet/LocateCourseMaterialsServlet?requestType=TERMS&storeId=10236&demoKey=d&programId=1105&_=");
            preg_match('/"data":\[\{(.+?)\}\]\}/', $terms, $groups);
            $terms = explode(",", $groups[1]);
            $term = explode(":", $terms[0]);
            $term = substr($term[1], 1, -1);

            $course = explode("-", $classID);
            $dep = $course[0];
            $course = $course[1];

            print '<a href="http://www.bkstr.com/webapp/wcs/stores/servlet/CourseMaterialsResultsView?catalogId=10001&categoryId=null&storeId=10236&langId=-1&programId=1105&termId='.$term.'&divisionDisplayName=%20&departmentDisplayName='.$dep.'&courseDisplayName='.$course.'&sectionDisplayName=01&demoKey=d&purpose=browse" target="_new">Get Books</a>';
        }

        public static function generateQS() {
            //this string concatenation could take longer than I'd like, but we need to do it...
            $qString = "./?";
            foreach($_REQUEST as $key=>$val) {
                if(isset($_COOKIE[$key])) {
                    continue;
                }
                if(is_array($val)) {
                    $qString .= $key."[]=".implode("&amp;".$key."[]=", $val)."&amp;";
                } else {
                    $qString .= $key."=".$val."&amp;";
                }
            }
            Course::$QS = $qString;
        }

        //for some definition of equal... make sure you don't check num registered here!
        public function equal($class) {
            if($this->isEmpty())
                return false;
            if($this->getCourseID() != $class->getCourseID())
                return false;
            if($this->getSection() != $class->getSection())
                return false;
            if($this->getTitle() != $class->getTitle())
                return false;
            return true;
        }
    }
?>