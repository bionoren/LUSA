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

	require_once($path."Object.php");

    /**
     * Stores information for an individual class meeting time.
     *
     * @author Bion Oren
     * @version 1.0
     */
    class Meeting extends Object {
        /** INTEGER Bit string of days of the week (1 is Sunday). */
        protected $days;
        /**
		 * BOOLEAN Returns true if this class is special (irregular day value or online class).
		 * Note that you should always be able to take special classes because they're special like that :).
		 */
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
        /** INTEGER Day of the year this meeting starts. */
        protected $startDay;
        /** INTEGER Day of the year this meeting ends. */
        protected $endDay;
        /** STRING The classroom this meeting is in. */
        protected $room;
        /** STRING The title of the class this meeting is in. */
        protected $title;

        /** STRING String representation of $startDay cached for display. */
        protected $startDayString;
        /** STRING String representation of $endDay cached for display. */
        protected $endDayString;
        /** STRING String representation of $days cached for display. */
        protected $dayString;
        /** STRING String representation of $startTime cached for display. */
        protected $startTimeString;
        /** STRING String representation of $endTime cached for display. */
        protected $endTimeString;

        /**
         * Constructs a new meeting time.
         *
         * @param SimpleXMLElement $meeting XML describing the meeting information.
         * @param STRING $campus Name of the campus this meeting is at.
		 * @param INTEGER $campusBitMask Bit string value for the given campus
		 * @param STRING $title The name of the class this meeting is for.
		 * @return VOID
         */
        public function __construct(SimpleXMLElement $meeting, $campus, $campusBitMask, $title) {
            $tmp = str_split((string)$meeting->{"meetingdaysofweek"});
            $temp = 0;
            for($i = 0; $i < count($tmp); $i++) {
                if($tmp[$i] != "-") {
                    $temp += pow(2, $i);
				}
            }
            $this->title = $title;
            $this->days = $temp;
            $this->startTime = Meeting::convertTime((string)$meeting->{"meetingstarttime"});
            $this->endTime = Meeting::convertTime((string)$meeting->{"meetingendtime"});
            $this->prof = (string)$meeting->{"profname"};
            $this->startDay = Meeting::getDateStamp((string)$meeting->{"meetingstartdate"});
            $this->endDay = Meeting::getDateStamp((string)$meeting->{"meetingenddate"});
            $this->campus = $campusBitMask;
            $this->campusName = $campus;
            $this->room = $meeting->{"meetingbuilding"}." ".$meeting->{"meetingroom"};

			$this->special = !is_numeric($this->days) || $this->days == 0 || $this->campusName == "XOL";

            $this->startDayString = date("n/j/y", $this->startDay);
            $this->endDayString = date("n/j/y", $this->endDay);
            $this->dayString = Meeting::dayString($this->days, $this->special);
            $this->startTimeString = Meeting::displayTime($this->startTime, $this->special);
            $this->endTimeString = Meeting::displayTime($this->endTime, $this->special);
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
		 * Returns the days this class is offered as a compact string.
		 *
		 * @param INTEGER $days Bit string of days of the week.
		 * @param BOOLEAN $online True if this is an online class (day of the week doesn't apply).
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
         * Returns the query string for this meeting to pass to to print.php.
         *
         * @return STRING Formatted course time information.
         */
        public function getPrintQS() {
            return implode("::", array($this->days,$this->startTime,$this->endTime,$this->room,urlencode($this->title)));
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
         * Returns a summary of this meeting for debugging purposes.
         *
         * @return STRING Meeting summary.
         */
        function __toString() {
            return $this->startDayString."-".$this->endDayString.", ".$this->dayString.", ".$this->startTimeString." - ".$this->endTimeString;
        }
    }
?>