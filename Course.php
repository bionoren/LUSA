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

	require_once("LabCourse.php");

    /**
     * Stores information for an individual class.
     *
     * @author Bion Oren
     * @version 2.0
     */
    class Course {
        /** STRING Stores a cache of the querystring. */
        public static $QS = "";

        //course specific
        /** STRING Course ID of the form DEPT-####. */
        protected $courseID;
		/** STRING Course ID with the section number appended. */
		protected $id;
        /** STRING Course section number. */
        protected $section;
        /** STRING Title of this class. */
        protected $title;
        /** INTEGER Number of students currently registered for this class. */
        protected $currentRegistered = 0;
        /** INTEGER Maximum number of students that can register for this class. */
        protected $maxRegisterable;

        //lecture/lab specific
        /** INTEGER Bit string of days of the week (1 is Sunday). */
        protected $days;
        /** FLOAT Class start time. */
        protected $startTime;
        /** FLOAT Class end time. */
        protected $endTime;
        /** INTEGER Day of the year this class starts on. */
        protected $startDay;
        /** INTEGER Day of the year this class ends on. */
        protected $endDay;
        /** STRING Name of the professor teaching this course. */
        protected $prof = "Staff";
        /** STRING Name of the campus this class is offered at. */
        protected $campus = "MAIN";
        /** STRING Type of class (online, traditional, nontraditional). */
        protected $type;
        /** COURSE Represents a lab class associated with this course. */
        protected $lab = null;

        /**
         * Constructs a new course object from the provided xml information.
         *
         * @param SimpleXMLElement $xml XML information for this class.
         * @return Course New class object.
         */
        public function __construct(SimpleXMLElement $xml) {
            //setup course info
            $this->courseID = substr($xml->{"coursenumber"}, 0, 4)."-".substr($xml->{"coursenumber"}, -4);
            $this->section = (string)$xml->{"sectionnumber"};
			$this->id = $this->getID().$this->getSection();
            if(empty($xml->{"sectiontitle"})) {
                $this->title = htmlspecialchars($xml->{"coursetitle"});
            } else {
                $this->title = htmlspecialchars($xml->{"sectiontitle"});
            }
            $this->currentRegistered = (string)$xml->{"currentnumregistered"};
            $this->maxRegisterable = (string)$xml->{"maxsize"};

            //setup lab/lecture specific stuff
            foreach($xml->{"meeting"} as $meet) {
                if($meet->{"meetingtypecode"} != "LB" || count($xml->{"meeting"}) == 1) {
                    $meeting = $meet;
                } else {
                    $this->lab = new LabCourse($xml, "LB");
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
                $this->campus = "Online";
            } elseif($this->isInternational()) {
				$this->campus = "Far Away";
			}
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
		 * Returns the days this class is offered as a compact string.
		 *
		 * @return STRING String of days this class is offered.
		 */
        function dayString() {
            if($this->isSpecial()) {
                return $this->getCampus();
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

		/**
		 * Displays this class in a table.
		 *
		 * @param BOOLEAN $optional True if this class is part of an optional set of classes.
		 * @return VOID
		 */
        public function display($optional=false) {
			print '<tr id="'.$this->getUID().'" class="'.$this->getBackgroundStyle().'"';
            if($optional) {
                print ' style="visibility:collapse;"';
            }
            print '>';
				if($optional) {
					$qstring = Course::$QS.'%sf[]='.$this->getUID().'&amp;submit=Filter';
					$filterLink = '<a href="'.$qstring.'" style="color:blue; text-decoration:underline;"><strong>%s</strong></a>';
					print '<td headers="classHeader">';
						printf($filterLink, "c", "Choose");
						print ' or ';
						printf($filterLink, "r", "Remove");
					print '</td>';
					print '<td style="width:auto;" headers="classHeader">';
						if(!$this->isSpecial()) {
							print "<input type='radio' id='select".$this->getUID()."' name='".$this->getID()."' value='".$this->getSection()."' onclick=\"selectClass('".$this->getID()."', '".$this->getPrintQS()."', '".Schedule::getPrintQS(Schedule::$common)."');\"/>";
							print "<label for='select".$this->getUID()."'>Preview</label>";
						}
					print "</td>";
				} else {
					print '<td headers="classHeader">'.$this->getID().'</td>';
					print '<td headers="classHeader">'.html_entity_decode($this->getTitle()).'</td>';
				}
				print '<td headers="profHeader">'.$this->getProf().'</td>';
				if(!Main::isTraditional()) {
					print '<td headers="dateHeader">'.date("n/j/y", $this->getStartDate()).' - '.date("n/j/y", $this->getEndDate()).'</td>';
				}
				print '<td headers="dayHeader">'.$this->dayString().'</td>';
				print '<td headers="timeHeader">'.Course::displayTime($this->getStartTime(), $this->isSpecial()).'-'.Course::displayTime($this->getEndTime(), $this->isSpecial()).'</td>';
				print '<td headers="sectionHeader">'.$this->getSection().'</td>';
				if(!Main::isTraditional()) {
					print '<td headers="campusHeader">'.$this->getCampus().'</td>';
				}
				print '<td headers="registeredHeader">'.$this->getCurrentRegistered().'/'.$this->getMaxRegistered().'</td>';
			print '</tr>';
			if($this->getLab() != null) {
				$this->lab->display($optional);
			}
        }

		/**
		 * Displays a link to the bookstore for the given class.
		 *
		 * @param STRING $classID Class ID.
		 * @return VOID
		 */
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
		 * Generates the URL prefix querystring to use for classes.
		 *
		 * @return VOID
		 */
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
            $qString = str_replace(" ", "%%20", $qString);
            Course::$QS = $qString;
        }

		/**
		 * Returns the correct CSS style to use for this class' background.
		 *
		 * @return STRING CSS class.
		 */
        protected function getBackgroundStyle() {
            //>5 seats left
            if($this->getMaxRegistered()-$this->getCurrentRegistered() > 5) {
                return 'status-open';
            } elseif($this->getMaxRegistered()-$this->getCurrentRegistered() > 0) {
            //<5 seats left
                return 'status-close';
            } else {
			//no seats left
				return 'status-full';
			}
        }

		/**
		 * Returns the name of the campus this class is at.
		 *
		 * @return STRING
		 * @see $campus
		 */
        public function getCampus() {
            return $this->campus;
        }

		/**
		 * Returns the number of people currently registered for this class.
		 *
		 * @return INTEGER
		 * @see $currentRegistered
		 */
        public function getCurrentRegistered() {
            return $this->currentRegistered;
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
         * Returns the days this class is offered.
         *
         * @return INTEGER
         * @see $days
         */
        public function getDays() {
            return $this->days;
        }

		/**
		 * Returns this class' end date.
		 *
		 * @return INTEGER
		 * @see $endDay
		 */
        public function getEndDate() {
            return $this->endDay;
        }

		/**
         * Returns this class' end time.
         *
         * @return FLOAT
         * @see $endTime
         */
        public function getEndTime() {
            return $this->endTime;
        }

		/**
         * Returns this class' ID.
         *
         * @return STRING
         * @see $courseID
         */
        public function getID() {
            return $this->courseID;
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
		 * Returns the maximum number of people that can be registered for this class.
		 *
		 * @return INTEGER
		 * @see $maxRegisterable
		 */
        public function getMaxRegistered() {
            return $this->maxRegisterable;
        }

		/**
		 * Returns the querystring used to show the print preview for this class.
		 *
		 * @return STRING Query string.
		 */
        public function getPrintQS() {
            $ret = implode("::", array($this->getDays(),$this->getStartTime(),$this->getEndTime(),addslashes($this->getTitle())));
			if($this->getLab() != null) {
				$ret .= "~".$this->getLab()->getPrintQS();
			}
			return $ret;
        }

		/**
		 * Returns the name of the prof teaching this class.
		 *
		 * @return STRING
		 * @see $prof
		 */
        public function getProf() {
            return $this->prof;
        }

        /**
         * Returns this class' section number.
         *
         * @return INTEGER
         * @see $sections
         */
        public function getSection() {
            return $this->section;
        }

		/**
         * Returns this class' start date.
         *
         * @return INTEGER
         * @see $startDay
         */
        public function getStartDate() {
            return $this->startDay;
        }

        /**
         * Returns this class' start time.
         *
         * @return FLOAT
         * @see $startTime
         */
        public function getStartTime() {
            return $this->startTime;
        }

		/**
		 * Returns this class' title.
		 *
		 * @return STRING
		 * @see $title
		 */
        public function getTitle() {
            return $this->title;
        }

		/**
		 * Returns this class' UID.
		 *
		 * @return STRING
		 * @see $id
		 */
        public function getUID() {
            return $this->id;
        }

		/**
		 * Returns true if this class is an international class.
		 *
		 * @return BOOLEAN
		 * @see $type
		 */
		protected function isInternational() {
			return $this->type == "IE";
		}

		/**
		 * Returns true if this class is online.
		 *
		 * @return BOOLEAN
		 * @see $type
		 */
        protected function isOnline() {
            return $this->type == "OL";
        }

		/**
		 * Returns true if nobody has a clue when this class is offered. This usually indicates an
		 * online, study abroad, or similar class.
		 *
		 * Note that you should always be able to take special classes because they're special like that :).
		 *
		 * @return BOOLEAN
		 */
		public function isSpecial() {
			return $this->getDays() == 0;
		}

		/**
		 * Returns this class' UID with an extra dash between the number and section number.
		 *
		 * @return STRING Formatted class UID.
		 */
        public function __toString() {
            return $this->getID()."-".$this->getSection();
        }
    }
?>