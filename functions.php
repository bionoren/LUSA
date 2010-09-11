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
			print "\$".$name." = ".$array."<br>\n";
			return;
		}
		foreach($array as $key=>$val) {
			if(is_array($val)) {
				dump($name."[".$key."]", $val);
			} else {
				print $name."[".$key."] = ";
                if(is_object($val) && !method_exists($val, "__toString")) {
                    print get_class($val)."<br>\n";
                } else {
                    print $val."<br>\n";
                }
			}
		}
	}

	//-----------------------------
	//			FUNCTIONS
	//-----------------------------

	/**
	 * Returns the data from the cache file for the given semester.
	 *
	 * @param STRING $semester Fully qualified semester name.
	 * @param BOOLEAN $trad True if traditional class data should be fetched.
	 * @return STRING cache data.
	 */
	function getCacheFile($semester, $trad) {
		$prefix = ($trad)?"":"Non";
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
            //grab this summer and try for this fall
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
		$sched = new Schedule();
		$invalid = null;
        foreach($courses as $i=>$sections) {
            if(count($sections) == 1) {
                Schedule::$common[] = $sections[0];
				$invalid .= $sched->validateClass($sections[0]);
                unset($courses[$i]);
            }
        }
        //the schedule still has common classes that need to be validated
        //just because there are no options doesn't mean you can take these classes
        if($invalid) {
            return $invalid;
        } elseif($courses == 0) {
            return array();
        }

		$sched = new Schedule();
        $schedules = array($sched);
        $conflict = null;
        foreach($courses as $sections) {
            $commonCandidate = false;
			$length = count($schedules);
			//careful with the length here! We're adding to this array while iterating over it!!
            for($key = 0; $key < $length; $key++) {
				$sched = $schedules[$key];
                foreach($sections as $section) {
                    if(Schedule::validateClassSections(array($sched), array($section))) {
                        $conflict = $sched->isValid();
                    } else {
                        $sched2 = clone $sched;
                        $sched->addClass($section);
                        $schedules[] = $sched;
						$commonCandidate = ($commonCandidate === $section)?$section:true;
						$sched = $sched2;
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
	 * Saves a cookie with the current course selection information
	 *
	 * @param STRING $data Data to save.
	 * @return VOID
	 */
    function save_cookie($data) {
        //set for ~2 months
        setcookie(Main::getCookieName(), $data, time()+60*60*24*7*8);
    }
?>