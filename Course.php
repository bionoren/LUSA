<?php
    class Course {
        protected static $ID = 1;
        //diff - Crs Start, Crs End, Campus
        //non-traditional keys
        public static $NON_KEYS = array("course", "section", "title", "start", "end", "prof", "maxReg", "curReg", "type", "days", "times", "campus", "bldg", "room");
        //traditional keys
        public static $KEYS = array("ref#", "course", "section", "title", "prof", "maxReg", "curReg", "type", "days", "times", "bldg", "room");
        public static $KEYS_SUMMER = array("ref#", "course", "section", "title", "start", "end", "prof", "maxReg", "curReg", "type", "days", "times", "bldg", "room");
        public static $QS = "";

        protected $id;
        protected $courseID;
        protected $section;
        protected $days;
        protected $startTime;
        protected $endTime;
        protected $startDay;
        protected $endDay;
        protected $title;
        protected $prof;
        protected $currentRegistered;
        protected $maxRegisterable;
        protected $campus;
        protected $type;

        public function __construct(array $dataArray) {
            $this->id = Course::$ID++;
            $this->courseID = $dataArray["course"];
            $this->section = $dataArray["section"];
            $this->days = $dataArray["days"];
            $this->startTime = Course::convertTime($dataArray["times"][0]);
            $this->endTime = Course::convertTime($dataArray["times"][1]);
            $this->title = $dataArray["title"];
            $this->prof = $dataArray["prof"];
            $this->currentRegistered = $dataArray["curReg"];
            $this->maxRegisterable = $dataArray["maxReg"];
            if(empty($this->currentRegistered)) {
                $this->currentRegistered = 0;
            }
            if(isset($dataArray["ref#"])) {
                $this->startDay = time();
                $this->endDay = time()+60*60*24*30*3;
                $this->campus = "MAIN";
            } else {
                $this->startDay = $this->getDateStamp($dataArray["start"]);
                $this->endDay = $this->getDateStamp($dataArray["end"]);
                $this->campus = $dataArray["campus"];
            }
            $this->type = $dataArray["type"];
            if($this->isOnline()) {
                $this->campus = "online";
            }
        }

        protected function getDateStamp($date) {
            if(empty($date))
                return time();
            $date = explode("/", $date);
            return mktime(1,1,1, $date[0], $date[1], $date[2]);
        }

        public static function convertTime($timestr) {
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

        public static function displayTime($time, $online=false) {
            if($online) {
                return "-";
            }
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
        public function mergeLabWithClass(array $class) {
            //array("course", "section", "title", "start", "end", "prof", "maxReg", "curReg", "type", "days", "times", "campus", "bldg", "room");
            $class["course"] = $this->courseID." lab";
            $class["section"] = $this->section;
            $class["title"] = $this->title." lab";
            $class["prof"] = $this->prof;
            $class["curReg"] = $this->currentRegistered;
            $class["maxReg"] = $this->maxRegisterable;
            $class["campus"] = $this->campus;
            return $class;
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

        public function getStartDate() {
            return $this->startDay;
        }

        public function getEndDate() {
            return $this->endDay;
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

        public function getCampus() {
            return $this->campus;
        }

        public function isOnline() {
            return $this->type == "OL";
        }

        public function display($optional=false) {
            if(empty(Course::$QS)) {
                Course::generateQS();
            }
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
            print '<tr id="'.$this->getID().'" class="'.$status.'"';
            if($optional) {
                print 'style="visibility:collapse;"';
            }
            print '>';
                if($optional) {
                    print "<td><input type='radio' name='".$this->getCourseID()."' value='".$this->getSection()."' onclick=\"load('".$this->getCourseID()."', '".$this->getPrintQS()."');\"></td>";
                    $qstring = Course::$QS.'cf[]='.$this->getID().'&amp;submit=Filter';
                    print '<td><a href="'.$qstring.'" style="color:red; text-decoration:none;">Remove</a></td>';
                } else {
                    print '<td>'.$this->getCourseID().'</td>';
                    print '<td>'.$this->getTitle().'</td>';
                }
                print '<td>'.$this->getProf().'</td>';
                if(!isTraditional()) {
                    print '<td>'.date("n/j/y", $this->startDay).' - '.date("n/j/y", $this->endDay).'</td>';
                }
                print '<td>'.$this->dayString().'</td>';
                print '<td>'.Course::displayTime($this->getStartTime(), $this->isOnline()).'-'.Course::displayTime($this->getEndTime(), $this->isOnline()).'</td>';
                print '<td>'.$this->getSection().'</td>';
                if(!isTraditional()) {
                    print '<td>'.$this->campus.'</td>';
                }
                print '<td>'.$this->getCurrentRegistered().'/'.$this->getMaxRegistered().'</td>';
            print '</tr>';
            return $ret;
        }

        function dayString() {
            if($this->isOnline()) {
                return "online";
            }
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

        public function getID() {
            return dechex($this->id);
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

            print '<a href="http://www.bkstr.com/webapp/wcs/stores/servlet/CourseMaterialsResultsView?catalogId=10001&categoryId=null&storeId=10236&langId=-1&programId=1105&termId='.$term.'&divisionDisplayName=%20&departmentDisplayName='.$dep.'&courseDisplayName='.$course.'&sectionDisplayName=01&demoKey=d&purpose=browse" target="_blank">Get Books</a>';
        }

        public function getPrintQS() {
            return implode("::", array($this->days,$this->startTime,$this->endTime,$this->title));
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

        public function __toString() {
            return $this->getCourseID()."-".$this->getSection();
        }
    }
?>