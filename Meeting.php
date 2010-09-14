<?php
    class Meeting {
        /** INTEGER Bit string of days of the week (1 is Sunday). */
        protected $days;
        /** BOOLEAN Returns true if this class is special (irregular day value or online class). */
		protected $special = false;
        /** FLOAT Class start time. */
        protected $startTime;
        /** FLOAT Class end time. */
        protected $endTime;
        /** STRING Name of the professor teaching this course. */
        protected $prof = "Staff";
        /** INTEGER Bitmask for the campus name. */
        protected $campus;
        /** STRING Name of the campus this class is offered at. */
        protected $campusName;
        protected $startDay;
        protected $endDay;

        protected $startDayString;
        protected $endDayString;
        protected $dayString;
        protected $startTimeString;
        protected $endTimeString;

        public function __construct(SimpleXMLElement $meeting, $campus, $campusBitMask) {
            $tmp = str_split((string)$meeting->{"meetingdaysofweek"});
            $temp = 0;
            for($i = 0; $i < count($tmp); $i++) {
                if($tmp[$i] != "-") {
                    $temp += pow(2, $i);
				}
            }
            $this->days = $temp;
            $this->startTime = Meeting::convertTime((string)$meeting->{"meetingstarttime"});
            $this->endTime = Meeting::convertTime((string)$meeting->{"meetingendtime"});
            $this->prof = (string)$meeting->{"profname"};
            $this->startDay = Meeting::getDateStamp((string)$meeting->{"meetingstartdate"});
            $this->endDay = Meeting::getDateStamp((string)$meeting->{"meetingenddate"});
            $this->campus = $campusBitMask;
            $this->campusName = $campus;

			$this->special = !is_numeric($this->days) || $this->days == 0 || $this->campusName == "XOL";

            $this->startDayString = date("n/j/y", $this->startDay);
            $this->endDayString = date("n/j/y", $this->endDay);
            $this->dayString = Meeting::dayString($this->days, $this->isSpecial());
            $this->startTimeString = Meeting::displayTime($this->startTime, $this->isSpecial());
            $this->endTimeString = Meeting::displayTime($this->endTime, $this->isSpecial());
        }

        public function display($nontrad) {
            if($nontrad) {
                $campus = $this->campusName;
                print '<td headers="campusHeader">'.$campus.'</td>';
            }
            print '<td headers="profHeader">'.$this->prof.'</td>';
            if($nontrad) {
                print '<td headers="dateHeader">'.$this->startDayString.' - '.$this->endDayString.'</td>';
            }
            print '<td headers="dayHeader">'.$this->dayString.'</td>';
			print '<td headers="timeHeader">'.$this->startTimeString.'-'.$this->endTimeString.'</td>';
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
		 * Returns the days this class is offered as a compact string.
		 *
		 * @param INTEGER $days Bit string of days of the week.
		 * @return STRING String of days this class is offered.
		 */
        public static function dayString($days, $online=false) {
            if($online) {
                return "Online";
            }

            $temp = array("U", "M", "T", "W", "R", "F", "S");
            $nums = array(1, 2, 4, 8, 16, 32, 64);
            $ret = "";
            for($i = 0; $i < count($temp); $i++) {
                if($days & $nums[$i]) {
                    $ret .= $temp[$i];
                } else {
                    $ret .= "-";
                }
            }
            return $ret;
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
         * Sorts the two classes by time.
         *
         * @param MEETING $class Other meeting.
         * @return INTEGER < 0 if this meeting is before, 0 if they are equal, > 0 if this meeting is after
         */
        function timeSort(Meeting $class) {
            //returns -1 if class1 is before class2
            return ($this->startTime - $class->startTime)*10; //return value needs to be +- 1. Otherwise, interpreted as 0
        }

        /**
		 * Checks if two classes are offered during at least 1 common day.
		 *
		 * @param MEETING $class Other meeting.
		 * @return BOOLEAN True if the classes overlap on at least 1 day.
		 */
		function isDateOverlap(Meeting $class) {
		   return !($this->endDay < $class->startDay || $class->endDay < $this->startDay);
		}

		/**
		 * Checks if two classes are offered on at least 1 common day of the week.
		 *
		 * @param MEETING $class Other class.
		 * @return BOOLEAN True if the classes overlap on at least 1 day.
		 */
		function isDayOverlap(Meeting $class) {
		   return $this->days & $class->days;
		}

        /**
         * Checks if two classes overlap.
         *
         * @param MEETING $class Other class.
         * @return MIXED False if no overlap, otherwise a string with the error message.
         */
        function isTimeConflict(Meeting $class) {
            //if one of the classes ends before the other one starts, no overlap
            return !($this->endTime < $class->startTime || $class->endTime < $this->startTime);
        }

        /**
         * Sorts the two classes.
         *
         * @param MEETING $class Other meeting.
         * @return INTEGER < 0 if the first class is before, 0 if they are equal, > 0 if the first class is after
         */
        function classSort(Meeting $class) {
            //if the classes aren't even on the same days, sort by days
            if(!$this->isDateOverlap($class)) {
                return $this->dateSort($class);
            }
            if(!$this->isDayOverlap($class)) {
                return $this->daySort($class);
            }
            return $this->timeSort($class1);
        }

        /**
         * Sorts the two classes by start date.
         *
         * @param MEETING $class Other meeting.
         * @return INTEGER < 0 if the first class is before, 0 if they are equal, > 0 if the first class is after
         */
        function dateSort(Meeting $class) {
            return $this->startDay - $class->startDay;
        }

        /**
         * Sorts the two classes by day.
         *
         * @param MEETING $class Other meeting.
         * @return INTEGER < 0 if the first class is before, 0 if they are equal, > 0 if the first class is after
         */
        function daySort(Meeting $class) {
            return $class->days - $this->days;
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
			return $this->special;
		}

        public function getCampus() {
            return $this->campus;
        }

        public function getPrintQS() {
            return implode("::", array($this->days,$this->startTime,$this->endTime));
        }
    }
?>