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
	require_once($path."Meeting.php");

    /**
     * Stores information for an individual class.
     *
     * @author Bion Oren
     * @version 3.0
     */
    class Course extends Object {
        //course specific
        /** STRING Course ID of the form DEPT-####. */
        protected $courseID;
		/** INTEGER The number of this course. */
		protected $number;
		/** STRING Course ID with the section number and a hash of all class info appended. */
		protected $uid;
        /** STRING Course section number. */
        protected $section;
        /** STRING Title of this class. */
        protected $title;
        /** INTEGER Number of students currently registered for this class. */
        protected $currentRegistered = 0;
        /** INTEGER Maximum number of students that can register for this class. */
        protected $maxRegisterable;
		/** MEETING List of meetings for this class (times, location, etc). */
		protected $meetings = array();
		/** BOOLEAN True if this is a traditional class. */
		protected $trad = true;
		/** INTEGER Bit string for the campus(es) this class is held at. */
		protected $campus = 0;

		/** BOOLEAN True if this class is part of a complex set of classes (IE COSC-1303 and COSC-2103. Something for which getLabel() can differ). */
		public $partOfSet = false;
		/** BOOLEAN True if this class is valid in the schedule. */
		public $valid = false;
		/** Course A course that this class conflicts with. */
		public $conflict = null;

        /**
         * Constructs a new course object from the provided xml information.
         *
         * @param SimpleXMLElement $xml XML information for this class.
         * @param BOOLEAN $trad True if this is a traditional class.9
         * @return Course New class object.
         */
        public function __construct(SimpleXMLElement $xml, $trad=true) {
            //setup course info
			$this->number = substr($xml->{"coursenumber"}, -4);
            $this->courseID = substr($xml->{"coursenumber"}, 0, 4)."-".$this->number;
            $this->section = (string)$xml->{"sectionnumber"};
			$this->uid = $this->courseID.$this->section;
            if(empty($xml->{"sectiontitle"})) {
                $this->title = htmlspecialchars($xml->{"coursetitle"});
            } else {
                $this->title = htmlspecialchars($xml->{"sectiontitle"});
            }
            $this->currentRegistered = (string)$xml->{"currentnumregistered"};
            $this->maxRegisterable = (string)$xml->{"maxsize"};
			$this->trad = $trad;
        }

		/**
		 * Adds a specific meeting time to this class.
		 *
		 * @param SimpleXMLElement $meeting Information for this class' location and time.
		 * @param STRING $campus Name of the campus this meeting is at.
		 * @param INTEGER $campusBitMask Bit string value for the given campus
		 * @return VOID
		 */
		public function addMeeting(SimpleXMLElement $meeting, $campus, $campusBitMask) {
			$this->uid .= md5($meeting->asXML());
			$temp = new Meeting($meeting, $campus, $campusBitMask, $this->getTitle());
			$this->meetings[] = $temp;
			$this->campus |= $temp->campus;
		}

		/**
		 * Returns the correct CSS style to use for this class' background.
		 *
		 * @return STRING CSS class.
		 */
        public function getBackgroundStyle() {
            //>5 seats left
            if($this->maxRegisterable-$this->currentRegistered > 5) {
                return 'status-open';
            } elseif($this->maxRegisterable-$this->currentRegistered > 0) {
            //<5 seats left
                return 'status-close';
            } else {
			//no seats left
				return 'status-full';
			}
        }

		/**
		 * Returns the bitmask for all campuses associated with this class.
		 *
		 * @return INTEGER Campus bitmask.
		 */
		public function getCampus() {
			return $this->campus;
		}

		/**
		 * Returns an array of error messages for all the conflicts this class has.
		 *
		 * @return Mixed Conflict message or false if no conflict.
		 */
		public function getConflict() {
			if($this->conflict instanceof this) {
				return false;
			} else {
				return $this->getTitle()." conflicts with ".$this->conflict->getTitle();
			}
		}

		/**
		 * Returns the label to use for this class in a dropdown.
		 *
		 * @return STRING Dropdown label text.
		 */
		public function getLabel() {
			return $this->number." ".$this->getTitle();
		}

		/**
		 * Returns the querystring used to show the print preview for this class.
		 *
		 * @return STRING Query string.
		 */
        public function getPrintQS() {
			$ret = array();
			foreach($this->meetings as $meeting) {
				$ret[] = $meeting->getPrintQS();
			}
			return implode("~", $ret);
        }

		/**
		 * Gets an array of professors and the meetings they teach.
		 *
		 * @return ARRAY List of meetings keyed by prof name.
		 */
		public function getProfClassList() {
			$ret = array();
			foreach($this->meetings as $meeting) {
				$ret[$meeting->prof] = $meeting;
			}
			return $ret;
		}

		/**
		 * Returns the title of this class.
		 *
		 * @return STRING Class title.
		 */
		public function getTitle() {
			return htmlspecialchars_decode($this->title);
		}

		/**
		 * Validates that you can take two classes together.
		 *
		 * @param COURSE $class The other class you're taking.
		 * @return BOOLEAN True if you can take both of these classes simultaneously.
		 */
		function validateClasses(Course $class) {
			foreach($this->meetings as $meeting) {
				foreach($class->meetings as $meeting2) {
					if($meeting->isDayOverlap($meeting2) && $meeting->isTimeConflict($meeting2) && $meeting->isDateOverlap($meeting2)) {
						return false;
					}
				}
			}
			return true;
		}

		/**
		 * Returns this class' UID with an extra dash between the number and section number.
		 *
		 * @return STRING Formatted class UID.
		 */
        public function __toString() {
            return $this->uid;
        }
    }
?>