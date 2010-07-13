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

    require_once("Course.php");

	/**
     * Stores information for a class that is a lab. Can be used by itself or as
     * a part of another Course.
     *
     * @author Bion Oren
     * @version 1.0
     */
    class LabCourse extends Course {
		/**
         * Constructs a new course object from the provided xml information.
         *
         * @param SimpleXMLElement $xml XML information for this class.
         * @return Course New class object.
         */
        public function __construct($xml) {
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
                if($meet->{"meetingtypecode"} == "LB") {
                    $meeting = $meet;
                    break;
                }
            }
            $this->type = "LB";
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
		 * Displays this class in a table.
		 *
		 * @param BOOLEAN $optional True if this class is part of an optional set of classes.
		 * @return VOID
		 */
        public function display($optional=false) {
			print '<tr id="'.$this->getUID().'lab" class="'.$this->getBackgroundStyle().'"';
            if($optional) {
                print ' style="visibility:collapse;"';
            }
            print '>';
				print '<td colspan="2" headers="classHeader"></td>';
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
        }
    }
?>