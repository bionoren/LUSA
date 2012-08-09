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
	 * @param INTEGER $today Timestamp used by unit tests. You are strongly discouraged from providing this parameter.
	 * @return ARRAY List of semester names.
	 */
    function getFileArray($reject=true, $today=null) {
        //rollover on May 1st, August 1st, and January 1st
		if(!$today) {
			$today = time();
		}
        $year = date("Y", $today);
        $month = date("n", $today);
        $day = date("j", $today);
        $files = array();
		$prefix = "cache/";
		$semesters = array("SP", "SU", "FA");
        //order is important here!
        if($month < 5) {
            $semester = 0;
        } elseif($month < 8) {
            $semester = 1;
        } else {
            $semester = 2;
        }

		//try to grab 2 semesters into the future, the current semester, and a year (3 semesters) into the past
		$numSem = count($semesters);
		for($i = -2; $i <= 3; $i++) {
			$index = ($semester-$i)%$numSem;
			while($index < 0) {
				$index += $numSem;
			}
			$sem = $semesters[$index];
			if($semester-$i >= $numSem) {
				$yr = $year + 1;
			} elseif($semester-$i < 0) {
				$yr = $year - 1;
			} else {
				$yr = $year;
			}
			if(!$reject || file_exists($prefix.$yr.$sem.".txt")) {
                $files[] = $yr.$sem;
            }
		}
        return $files;
	}

    /**
	 * Creates a list of classes that can be taken (i.e. do not cause conflicts with other classes and can be used
	 * in a valid schedule.) from the given courses.
	 *
	 * Note that this function (the while loop in particular) is a significant performance bottleneck
	 *
	 * @param ARRAY $courses List of course objects to consider.
	 * @return MIXED A list of valid classes or a string with the error message(s).
	 */
	function findSchedules(array $courses) {
		usort($courses, function($section1, $section2) {
			return count($section1) - count($section2);
		});
		$numCourses = count($courses);
        $indexes = array_fill(0, $numCourses, 0);
		$courseCounts = array();
		foreach($courses as $arr) {
			$courseCounts[] = count($arr);
		}

		$classes = array();
		for($i = 0; $i < $numCourses; $i++) {
			$classes[$i] = $courses[$i][0];
		}
		while(true) {
			if(isValidSchedule($classes)) {
				foreach($classes as $class) {
					$class->conflict = null;
					$class->valid = true;
				}
			}
			//for each course, if the index for this course is less than the max section index, shift it
            //also handles rollover for previous indicies, and updates the currently evaluated classes array
            for($i = 0, $classes[0] = @$courses[0][++$indexes[0]]; $indexes[$i] == $courseCounts[$i];) {
				$classes[$i] = $courses[$i][0];
                $indexes[$i++] = 0;
                //this exits the loop
                if($i == $numCourses) break 2;
				@$classes[$i] = $courses[$i][++$indexes[$i]];
            }
        }

		$conflict = findConflicts($courses);
        if(!empty($conflict)) {
            return implode("<br>", $conflict);
        }
        return $courses;
	}

	/**
	 * Validates a set of classes.
	 *
	 * @param ARRAY $classes - List of classes to take together.
	 * @return BOOLEAN True if the classes can be taken together.
	 */
	function isValidSchedule(array $classes) {
		foreach($classes as $k1=>$class1) {
			if($class1->valid) {
				continue;
			}
			foreach($classes as $k2=>$class2) {
				if($k1 == $k2) {
					continue;
				}
				if(!$class1->validateClasses($class2)) {
					$class1->conflict = $class2;
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Builds and returns an array of conflicts.
	 *
	 * @param ARRAY $courses List of courses to find conflicts for.
	 * @param BOOL $fast If true, fails as soon as an error is encountered.
	 * @return ARRAY List of unresolvable conflicts.
	 */
	function findConflicts(array &$courses, $fast=false) {
		$conflict = array();
		foreach($courses as $key1=>$sections) {
			$tmp = array();
			foreach($sections as $key2=>$section) {
				if(!$section->valid) {
					if($section->conflict) {
						$tmp[] = $section->getConflict();
					}
					unset($courses[$key1][$key2]);
				}
			}
			if(empty($courses[$key1])) {
				if($fast) {
					return array(@$tmp[0]);
				}
				$conflict = array_merge($conflict, $tmp);
			} else {
				$courses[$key1] = array_values($courses[$key1]);
			}
		}
		return $conflict;
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