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

	//DEBUGGING FUNCTIONS
	function dump($name, $array, $member=null) {
		if(!is_array($array)) {
			print "$name = $array<br/>";
		} else {
			foreach($array as $key=>$val) {
				if(is_array($val)) {
                    if($member == null)
    					dump($name."[$key]", $val, $member);
                    else
                        dump($name."[$key]", $val);
                } else {
                    if($member == null) {
    					print $name."[".$key."] = ".$val."<br/>";
                    } else {
                        print $name."[".$key."] = ".$val->{$member}()."<br/>";
                    }
                }
			}
		}
	}

	//FUNCTIONS
    function isTraditional() {
        return !isset($_REQUEST["type"]) || $_REQUEST["type"] == "trad";
    }

    function save_cookie($data) {
        //set for ~2 months
        setcookie("lastSchedule", $data, time()+60*60*24*7*8);
    }

    function getCurrentSemester($year, $semester, $trad) {
        //get the current class schedule from LeTourneau
		$file = getCacheFile($year, $semester, $trad);
        $title = fgets($file);
        fclose($file);
        return $title;
    }

	function getCacheFile($year, $semester, $trad) {
		if(!$trad) {
            $prefix = "non";
        } else {
            $prefix = "";
        }
		$name = "cache/".$prefix.$year.$semester.".txt";
		if(!file_exists($name)) {
            //send the user back after 5 seconds
            print '<script language="javascript">setTimeout("history.back()",5000);</script>';
            die("There is no data available for $semester $year");
        }
        return fopen($name, "r");
	}

    function getFileArray($reject=true) {
        //rollover on May 1st, August 1st, and January 1st
        $year = date("Y");
        $month = date("n");
        $day = date("j");
        $files = array();
		$prefix = "cache/";
        //order is important here!
        if($month < 5) {
            //this spring and try for this summer and fall
            if(!$reject || file_exists($prefix.$year."FA.txt"))
                $files[] = array($year, "FA");
            if(!$reject || file_exists($prefix.$year."SU.txt"))
                $files[] = array($year, "SU");
            $files[] = array($year, "SP");
        } elseif($month < 8) {
            //grab this summer and try for next fall
            if(!$reject || file_exists($prefix.$year."FA.txt"))
                $files[] = array($year, "FA");
            $files[] = array($year, "SU");
        } else {
            //grab this fall and try for next spring
            if(!$reject || file_exists($prefix.($year+1)."SP.txt"))
                $files[] = array($year+1, "SP");
            $files[] = array($year, "FA");
        }
        return $files;
    }

	function getClassData($year, $semester, $trad, $campus) {
        $file = getCacheFile($year, $semester, $trad);
        $classes = array();
        fgets($file); //burn the title
        while(!feof($file)) {
            $class = unserialize(fgets($file));
            if($class->isOnline() || $class->getCampus() == $campus) {
                $classes[] = $class;
            }
        }
        fclose($file);
        return $classes;
	}

    //filters the master class list down to the courses we're interested in and organizes the data into something parsable by evaluateSchedules()
	function findSchedules(array $courses) {
        //add course information for all the courses to be taken
        //classes with only one section must be common
        foreach($courses as $i=>$sections) {
            if(count($sections) == 1) {
                Schedule::$common[] = $sections[0];
                unset($courses[$i]);
            }
        }
        $sched = new Schedule(array());
        $valid = $sched->isValid();
        //the schedule still has common classes that need to be validated
        //just because there are no options doesn't mean you can take these classes
        if($valid !== true) {
            return $valid;
        } elseif($courses == 0) {
            return array();
        }

        $schedules = array($sched);
        $conflict = null;
        foreach($courses as $sections) {
            $commonCandidate = false;
            foreach($schedules as $key=>$sched) {
                foreach($sections as $section) {
                    if($sched->validateClass($section) === true) {
                        $sched2 = clone $sched;
                        $sched->addClass($section);
                        $schedules[] = $sched;
                        $sched = $sched2;
                        if(!$commonCandidate || $commonCandidate === $section) {
                            $commonCandidate = $section;
                        } else {
                            $commonCandidate = true;
                        }
                    } else {
                        $conflict = $sched->isValid();
                    }
                }
            }
            if(is_object($commonCandidate)) {
                Schedule::$common[] = $commonCandidate;
            }
        }
        if(count($schedules) == 0) {
            return $conflict;
        }

		return $schedules;
	}

	function classSort(Course $class1, Course $class2) {
        //if the classes aren't even on the same days, sort by days
		if(!isDateOverlap($class1, $class2)) {
            return dateSort($class1, $class2);
        }
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

    function dateSort(Course $class1, Course $class2) {
        return $class1->getStartDate() - $class2->getStartDate();
    }

	function checkTimeConflict(Course $class1, Course $class2) {
        //if one of the classes ends before the other one starts, no overlap
        if($class1->getEndTime() < $class2->getStartTime() || $class2->getEndTime() < $class1->getStartTime()) {
            return false;
        } elseif($class1->getCourseID() == $class2->getCourseID()) {
            return $class1->getTitle()." conflicts with itself";
        }
        return $class1->getTitle()." conflicts with ".$class2->getTitle();
	}

	function isDayOverlap(Course $class1, Course $class2) {
        return ($class1->getDays() & $class2->getDays()) > 0;
	}

    function isDateOverlap(Course $class1, Course $class2) {
        return $class1->getEndDate() >= $class2->getStartDate() && $class2->getEndDate() >= $class1->getStartDate();
    }
?>