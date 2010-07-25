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