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

	require_once("Main.php");

	//-----------------------------
	//	   DEBUGGING FUNCTIONS
	//-----------------------------

	/**
     * useful debug function that displays variables or arrays in a pretty format.
     *
     * @param STRING $name Name of the array (for pretty display purposes).
     * @param MIXED $array Array of data, but if it isn't an array we try to print it by itself.
     * @return VOID
     */
	function dump($name, $array) {
		if(!is_array($array)) {
			print "\$".$name." = ".$array."<br>";
			return;
		}
		foreach($array as $key=>$val) {
			if(is_array($val)) {
				dump($name."[".$key."]", $val);
			} else {
				print $name."[".$key."] = ";
                if(is_object($val)) {
                    print get_class($val)."<br>";
                } else {
                    print $val."<br>";
                }
			}
		}
	}

	//-----------------------------
	//			FUNCTIONS
	//-----------------------------

	/**
	 * Checks if two classes overlap.
	 *
	 * @param COURSE $class1 First class.
	 * @param COURSE $class2 Second class.
	 * @return MIXED False if no overlap, otherwise a string with the error message.
	 */
	function checkTimeConflict(Course $class1, Course $class2) {
        //if one of the classes ends before the other one starts, no overlap
        if($class1->getEndTime() < $class2->getStartTime() || $class2->getEndTime() < $class1->getStartTime()) {
            return false;
        }
        return $class1->getTitle()." conflicts with ".$class2->getTitle();
	}

	/**
	 * Sorts the two classes.
	 *
	 * @param COURSE $class1 First class.
	 * @param COURSE $class2 Second class.
	 * @return INTEGER < 0 if the first class is before, 0 if they are equal, > 0 if the first class is after
	 */
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

	/**
	 * Sorts the two classes by start date.
	 *
	 * @param COURSE $class1 First class.
	 * @param COURSE $class2 Second class.
	 * @return INTEGER < 0 if the first class is before, 0 if they are equal, > 0 if the first class is after
	 */
    function dateSort(Course $class1, Course $class2) {
        return $class1->getStartDate() - $class2->getStartDate();
    }

	/**
	 * Sorts the two classes by day.
	 *
	 * @param COURSE $class1 First class.
	 * @param COURSE $class2 Second class.
	 * @return INTEGER < 0 if the first class is before, 0 if they are equal, > 0 if the first class is after
	 */
	function daySort(Course $class1, Course $class2) {
		return $class2->getDays() - $class1->getDays();
	}

	/**
	 * Returns the data from the cache file for the given semester.
	 *
	 * @param STRING $semester Fully qualified semester name.
	 * @param BOOLEAN $trad True if traditional class data should be fetched.
	 * @return STRING cache data.
	 */
	function getCacheFile($semester, $trad) {
		$prefix = ($trad)?"":"non";
		$name = "cache/".$prefix.$semester.".txt";
		if(!file_exists($name)) {
            //send the user back after 5 seconds
            print '<script language="javascript">setTimeout("history.back()",5000);</script>';
            die("There is no data available for $semester");
        }
        return file_get_contents($name);
	}

	/**
	 * Returns an array of classes for the given options.
	 *
	 * @param STRING $semester Fully qualified semester name.
	 * @param BOOLEAN $trad True for traditional classes.
	 * @return ARRAY Array of Course objects.
	 */
	function getClassData($semester, $trad) {
		return unserialize(getCacheFile($semester, $trad));
	}

	/**
	 * Returns an array of fully qualified semester names.
	 *
	 * @param BOOLEAN $reject If false, semesters without available data are included.
	 * @return ARRAY List of semester names.
	 */
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
                $files[] = $year."FA";
            if(!$reject || file_exists($prefix.$year."SU.txt"))
                $files[] = $year."SU";
            $files[] = $year."SP";
        } elseif($month < 8) {
            //grab this summer and try for next fall
            if(!$reject || file_exists($prefix.$year."FA.txt"))
                $files[] = $year."FA";
            $files[] = $year."SU";
        } else {
            //grab this fall and try for next spring
            if(!$reject || file_exists($prefix.($year+1)."SP.txt"))
                $files[] = ($year+1)."SP";
            $files[] = $year."FA";
        }
        return $files;
    }

    /**
	 * Creates a list of possible schedules from the given courses and filters out the invalid ones.
	 *
	 * @param ARRAY $courses List of course objects to consider.
	 * @return MIXED A list of valid schedules or a string with the error message(s).
	 */
	function findSchedules(array $courses) {
        //add course information for all the courses to be taken
        //classes with only one section must be common
        foreach($courses as $i=>$sections) {
            if(count($sections) == 1) {
                Schedule::$common[] = $sections[0];
                unset($courses[$i]);
            }
        }
        $sched = new Schedule();
		$invalid = $sched->validate();
        //the schedule still has common classes that need to be validated
        //just because there are no options doesn't mean you can take these classes
        if($invalid) {
            return $invalid;
        } elseif($courses == 0) {
            return array();
        }

        $schedules = array($sched);
        $conflict = null;
        foreach($courses as $sections) {
            $commonCandidate = false;
            foreach($schedules as $key=>$sched) {
                foreach($sections as $section) {
                    if($sched->validateClass(null, $section)) {
                        $conflict = $sched->isValid();
                    } else {
                        $sched2 = clone $sched;
                        $sched->addClass($section);
                        $schedules[] = $sched;
                        $sched = $sched2;
                        if(!$commonCandidate || $commonCandidate === $section) {
                            $commonCandidate = $section;
                        } else {
                            $commonCandidate = true;
                        }
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

	/**
	 * Checks if two classes are offered during at least 1 common day.
	 *
	 * @param COURSE $class1 First class.
	 * @param COURSE $class2 Second class.
	 * @return BOOLEAN True if the classes overlap on at least 1 day.
	 */
    function isDateOverlap(Course $class1, Course $class2) {
        return !($class1->getEndDate() < $class2->getStartDate() || $class2->getEndDate() < $class1->getStartDate());
    }

	/**
	 * Checks if two classes are offered on at least 1 common day of the week.
	 *
	 * @param COURSE $class1 First class.
	 * @param COURSE $class2 Second class.
	 * @return BOOLEAN True if the classes overlap on at least 1 day.
	 */
	function isDayOverlap(Course $class1, Course $class2) {
        return $class1->getDays() & $class2->getDays();
	}

	/**
	 * Saves a cookie with the current course selection information
	 *
	 * @param STRING $data Data to save.
	 * @return VOID
	 */
    function save_cookie($data) {
        //set for ~2 months
        setcookie(Main::getCookieName(), $data, time()+60*60*24*7*8);
    }

	/**
	 * Sorts the two classes by time.
	 *
	 * @param COURSE $class1 First class.
	 * @param COURSE $class2 Second class.
	 * @return INTEGER < 0 if the first class is before, 0 if they are equal, > 0 if the first class is after
	 */
    function timeSort(Course $class1, Course $class2) {
        $start1 = $class1->getStartTime();
        $start2 = $class2->getStartTime();
        //returns -1 if class1 is before class2
        return ($start1 - $start2)*10; //return value needs to be +- 1. Otherwise, interpreted as 0
    }

	/**
	 * Validates that you can take two classes together.
	 *
	 * @param COURSE $class1 The first class you're taking.
	 * @param COURSE $class2 The second class you're taking.
	 * @return BOOLEAN True if you can take both of these classes simultaneously.
	 */
	function validateClasses(Course $class1, Course $class2) {
		if(isDayOverlap($class1, $class2) && isDateOverlap($class1, $class2)) {
			return checkTimeConflict($class1, $class2);
		}
	}
?>