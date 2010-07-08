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

    /**
     * Stores information for an individual class.
     *
     * @author Bion Oren
     * @version 2.0
     */
    class Course {
        /** Stores a cache of the querystring. */
        //TODO why??
        public static $QS = "";

        //course specific
        /** Course ID of the form DEPT-####. */
        protected $courseID;
		/** Course ID with the section number appended. */
		protected $id;
        /** Course section number. */
        protected $section;
        /** Title of this class. */
        protected $title;
        /** Number of students currently registered for this class. */
        protected $currentRegistered = 0;
        /** Maximum number of students that can register for this class. */
        protected $maxRegisterable;

        //lecture/lab specific
        /** Bit string of days of the week (1 is Sunday). */
        protected $days;
        /** Floating point representation of class start time. */
        protected $startTime;
        /** Floating point representation of class end time. */
        protected $endTime;
        /** Day of the year this class starts on. */
        protected $startDay;
        /** Day of the year this class ends on. */
        protected $endDay;
        /** Name of the professor teaching this course. */
        protected $prof = "Staff";
        /** Name of the campus this class is offered at. */
        protected $campus = "MAIN";
        /** Type of class (online, traditional, nontraditional). */
        protected $type;
        /** Instance of Course representing a lab class associated with this course. */
        protected $lab = null;

        /**
         * Constructs a new course object from the provided xml information.
         *
         * @param SimpleXMLElement $xml XML information for this class.
         * @param STRING $type One of LC for lecture or LB for lab.
         * @return Course New class object.
         */
        public function __construct(SimpleXMLElement $xml, $type="LC") {
            //setup course info
            $this->courseID = substr($xml->{"coursenumber"}, 0, 4)."-".substr($xml->{"coursenumber"}, -4);
            $this->section = (string)$xml->{"sectionnumber"};
			$this->id = $this->getCourseID().$this->getSection();
            if(empty($xml->{"sectiontitle"})) {
                $this->title = htmlspecialchars($xml->{"coursetitle"});
            } else {
                $this->title = htmlspecialchars($xml->{"sectiontitle"});
            }
            $this->currentRegistered = (string)$xml->{"currentnumregistered"};
            $this->maxRegisterable = (string)$xml->{"maxsize"};

            //setup lab/lecture specific stuff
            foreach($xml->{"meeting"} as $meet) {
                if($meet->{"meetingtypecode"} == $type) {
                    $meeting = $meet;
                } elseif($meet->{"meetingtypecode"}) {
                    $meeting = $meet;
                } else {
                    $lab = new Course($xml, "LB");
                }
            }
            $this->type = (string)$meeting->{"meetingtypecode"};
            $tmp = str_split((string)$meeting->{"meetingdaysofweek"});
            $temp = 0;
            for($i = 0; $i < count($tmp); $i++) {
                if($tmp[$i] != "-")
                    $temp += pow(2, $i);
            }
            $this->days = $temp;
            $this->startTime = Course::convertTime((string)$meeting->{"meetingstarttime"});
            $this->endTime = Course::convertTime((string)$meeting->{"meetingendtime"});
            $this->prof = (string)$meeting->{"profname"};
            $this->startDay = Course::getDateStamp((string)$meeting->{"meetingstartdate"});
            $this->endDay = Course::getDateStamp((string)$meeting->{"meetingenddate"});
            if(isset($meeting->{"campus"})) {
                $this->campus = (string)$meeting->{"campus"};
            }
            if($this->isOnline()) {
                $this->campus = "online";
            }
        }

        /**
         * Returns the lab associated with this class.
         *
         * @return Course Lab class (or null if none).
         */
        public function getLab() {
            return $this->lab;
        }

        /**
         * Returns the datestamp for the given date.
         *
         * @param STRING $date Date of the format YYYY-MM-DD
         * @return INTEGER Timestamp associated with 1:01:01 AM of this day (system timezone),
         *                  or the current time if $date is empty.
         */
        public static function getDateStamp($date) {
            if(empty($date))
                return time();
            $date = explode("-", $date);
            return mktime(1,1,1, $date[1], $date[2], $date[0]);
        }

        /**
         * Converts a time string to its integer equivalent.
         *
         * Returns "TBA" for classes with a time of "TBA".
         *
         * @param STRING $timestr String in the format HH:MM.
         * @return INTEGER Hours + minutes.
         */
        public static function convertTime($timestr) {
            if($timestr == "TBA")
                return $timestr;
            //convert minutes into a decimal
            $hours = substr($timestr, 0, strlen($timestr)-2);
            $minutes = intval(substr($timestr, -2))/60;
            return $hours+$minutes;
        }

        /**
         * Displays the given time as a string.
         *
         * Returns "-" for online classes and "TBA" for classes with a
         * time of "TBA".
         *
         * @param FLOAT $time Floating point representation of a time.
         * @param BOOLEAN $online True if this is an online class.
         * @return STRING Time in the format HH:MM('a'|'p')
         */
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
            if(isset($time[1])) {
                //make the minutes a decimal number again
                $time[1] = ".".$time[1];
                //convert the decimal back to minutes
                $time[1] = round($time[1]*60);
                //add a leading zero if 0-9 minutes
                if($time[1] < 10)
                    $time[1] = "0".$time[1];
            } else {
                $time[1] = "00";
            }
            //return the time
            return $time[0].":".$time[1].$ap;
        }

        /**
         * Returns this class' course ID.
         *
         * @return STRING {@see $courseID}
         */
        public function getCourseID() {
            return $this->courseID;
        }

        /**
         * Returns this class' section number.
         *
         * @return INTEGER {@see $sections}
         */
        public function getSection() {
            return $this->section;
        }

        /**
         * Returns the days this class is offered.
         *
         * @return INTEGER {@see $days}
         */
        public function getDays() {
            return $this->days;
        }

        /**
         * Returns this class' start time.
         *
         * @return FLOAT {@see $startTime}
         */
        public function getStartTime() {
            return $this->startTime;
        }

        /**
         * Returns this class' end time.
         *
         * @return FLOAT {@see $endTime}
         */
        public function getEndTime() {
            return $this->endTime;
        }

        /**
         * Returns this class' start date.
         *
         * @return INTEGER {@see $startDay}
         */
        public function getStartDate() {
            return $this->startDay;
        }

        public function getEndDate() {
            return $this->endDay;
        }

        public function getTitle() {
            return htmlspecialchars($this->title);
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

        public function getID() {
            return $this->getCourseID().$this->getSection();
        }

        public function display($optional=false) {
            if(empty(Course::$QS)) {
                Course::generateQS();
            }
            print '<tr id="'.$this->getID().'" class="'.$this->getBackgroundStyle().'"';
            if($optional) {
                print ' style="visibility:collapse;"';
            }
            print '>';
                if($optional) {
                    $qstring = Course::$QS.'cf[]='.$this->getID().'&amp;submit=Filter';
                    print '<td><a href="'.$qstring.'" style="color:blue; text-decoration:underline;"><strong>Choose</strong></a>';
                    $qstring = Course::$QS.'rf[]='.$this->getID().'&amp;submit=Filter';
                    print ' or <a href="'.$qstring.'" style="color:blue text-decoration:underline;"><strong>Remove</strong></a></td>';
                    print "<td><input type='radio' id='select".$this->getCourseID().$this->getSection()."' alt='Select' name='".$this->getCourseID()."' value='".$this->getSection()."' onclick=\"selectClass('".$this->getCourseID()."', '".$this->getPrintQS()."', '".Schedule::getPrintQS(Schedule::$common)."');\"/>";
                    print "<label for='select".$this->getCourseID().$this->getSection()."'>Preview</label></td>";
                } else {
                    print '<td>'.$this->getCourseID().'</td>';
                    print '<td>'.html_entity_decode($this->getTitle()).'</td>';
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
        }

        protected function getBackgroundStyle() {
            //>5 seats left
            if($this->getMaxRegistered()-$this->getCurrentRegistered() > 5) {
                return 'status-open';
            } elseif($this->getMaxRegistered()-$this->getCurrentRegistered() <= 5 && (int)$this->getMaxRegistered() > (int)$this->getCurrentRegistered()) {
            //<5 seats left
                return 'status-close';
            }
            //no seats left
            return 'status-full';
        }

        function dayString() {
            if($this->isOnline()) {
                return "online";
            }
            $temp = array("U", "M", "T", "W", "R", "F", "S");
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
            return implode("::", array($this->days,$this->startTime,$this->endTime,addslashes($this->title)));
        }

        public static function generateQS() {
            //this string concatenation could take longer than I'd like, but we need to do it...
            $qString = $_SERVER["PHP_SELF"]."?";
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
            $qString = str_replace(" ", "%20", $qString);
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