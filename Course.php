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

	require_once("Meeting.php");

    /**
     * Stores information for an individual class.
     *
     * @author Bion Oren
     * @version 3.0
     */
    class Course {
        /** STRING Stores a cache of the querystring. */
        public static $QS = "";

        //course specific
        /** STRING Course ID of the form DEPT-####. */
        protected $courseID;
		/** INTEGER The number of this course. */
		protected $number;
		/** STRING Course ID with the section number and a hash of all class info appended. */
		protected $id;
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
		/** BOOLEAN Returns true if this class is special (irregular day value or online class). */
		protected $special = false;
		/** BOOLEAN True if this is a traditional class. */
		protected $trad = true;

        /**
         * Constructs a new course object from the provided xml information.
         *
         * @param SimpleXMLElement $xml XML information for this class.
         * @return Course New class object.
         */
        public function __construct(SimpleXMLElement $xml, $trad=true) {
            //setup course info
			$this->number = substr($xml->{"coursenumber"}, -4);
            $this->courseID = substr($xml->{"coursenumber"}, 0, 4)."-".$this->number;
            $this->section = (string)$xml->{"sectionnumber"};
			$this->id = $this->courseID.$this->section;
            if(empty($xml->{"sectiontitle"})) {
                $this->title = htmlspecialchars($xml->{"coursetitle"});
            } else {
                $this->title = htmlspecialchars($xml->{"sectiontitle"});
            }
            $this->currentRegistered = (string)$xml->{"currentnumregistered"};
            $this->maxRegisterable = (string)$xml->{"maxsize"};
			$this->trad = $trad;
        }

//		* @param SimpleXMLElement $meeting XML information for this class' location and time.
		public function addMeeting(SimpleXMLElement $meeting, $campus, $campusBitMask) {
			$this->id .= md5($meeting->asXML());
			$this->meetings[] = new Meeting($meeting, $campus, $campusBitMask);
		}

		/**
		 * Finishes cashing information relating to meeting data.
		 */
		public function finalize() {
			$this->special = true;
			foreach($this->meetings as $meeting) {
				$this->special = $this->special && $meeting->isSpecial();
			}
		}

		/**
		 * Displays this class in a table.
		 *
		 * @param BOOLEAN $optional True if this class is part of an optional set of classes.
		 * @return VOID
		 */
        public function display($optional=false) {
			print '<tr id="'.$this->getUID().'0" class="'.$this->getBackgroundStyle().'"';
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
							print "<input type='radio' id='select".$this->getUID()."' name='".$this->getID()."' value='".$this->section."' onclick=\"selectClass('".$this->getID()."', '".$this->getPrintQS()."', '".Schedule::getPrintQS(Schedule::$common)."');\"/>";
							print "<label for='select".$this->getUID()."'>Preview</label>";
						}
					print "</td>";
				} else {
					print '<td headers="classHeader">'.$this->getID().'</td>';
					print '<td headers="classHeader">'.$this->title.'</td>';
				}
                print '<td headers="sectionHeader">'.$this->section.'</td>';
                $this->meetings[0]->display(!$this->trad);
                print '<td headers="registeredHeader">'.$this->currentRegistered.'/'.$this->maxRegisterable.'</td>';
			print '</tr>';
            for($i = 1; $i < count($this->meetings); $i++) {
                print '<tr id="'.$this->getUID().$i.'" class="'.$this->getBackgroundStyle().'"';
                    if($optional) {
                        print ' style="visibility:collapse;"';
                    }
                    print '>';
                    print '<td colspan="3">&nbsp;</td>';
                    $this->meetings[$i]->display(!$this->trad);
                    print '<td></td>';
                print '</tr>';
            }
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

		public function getCampus() {
			$ret = 0;
			foreach($this->meetings as $meeting) {
				$ret |= $meeting->getCampus();
			}
			return $ret;
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
		 * Returns the label to use for this class in a dropdown.
		 *
		 * @return STRING Dropdown label text.
		 */
		public function getLabel() {
			return $this->number." ".$this->title;
		}

		public function getNumMeetings() {
			return count($this->meetings);
		}

		/**
		 * Returns the querystring used to show the print preview for this class.
		 *
		 * @return STRING Query string.
		 */
        public function getPrintQS() {
			$ret = array();
			foreach($this->meetings as $meeting) {
				$ret[] = $meeting->getPrintQS()."::".$this->title;
			}
			return rawurlencode(htmlspecialchars_decode(implode("~", $ret)));
        }

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

		public function isSpecial() {
			return $this->special;
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
					if(!($meeting->isDayOverlap($meeting2) && $meeting->isDateOverlap($meeting2) && $meeting->isTimeConflict($meeting2))) {
						return true;
					}
				}
			}
			return false;
		}

		/**
		 * Returns this class' UID with an extra dash between the number and section number.
		 *
		 * @return STRING Formatted class UID.
		 */
        public function __toString() {
            return $this->getUID();
        }
    }
?>