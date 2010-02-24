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
			print "$name = $array<br>";
		} else {
			foreach($array as $key=>$val) {
				if(is_array($val)) {
                    if($member == null)
    					dump($name."[$key]", $val, $member);
                    else
                        dump($name."[$key]", $val);
                } else {
                    if($member == null) {
    					print $name."[".$key."] = ".$val."<br>";
                    } else {
                        print $name."[".$key."] = ".$val->{$member}()."<br>";
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

    function getCurrentSemester($year=null, $semester=null, $trad) {
        //get the current class schedule from LeTourneau
        if(!$trad) {
            $prefix = "non";
        }
		if(!file_exists($prefix.$year.$semester.".txt")) {
            //send the user back after 5 seconds
            print '<script language="javascript">setTimeout("history.back()",5000);</script>';
            die("There is no data available for $semester $year");
        }
        $file = fopen($prefix.$year.$semester.".txt", "r");
        $title = fgets($file);
        fclose($file);
        return $title;
    }

    function getFileArray($reject=true) {
        //rollover on May 1st, August 1st, and January 1st
        $year = date("Y");
        $month = date("n");
        $day = date("j");
        $files = array();
        //order is important here!
        if($month < 5) {
            //this spring and try for this summer and fall
            if(!$reject || file_exists($year."FA.txt"))
                $files[] = array($year, "FA");
            if(!$reject || file_exists($year."SU.txt"))
                $files[] = array($year, "SU");
            $files[] = array($year, "SP");
        } elseif($month < 8) {
            //grab this summer and try for next fall
            if(!$reject || file_exists($year."FA.txt"))
                $files[] = array($year, "FA");
            $files[] = array($year, "SU");
        } else {
            //grab this fall and try for next spring
            if(!$reject || file_exists(($year+1)."SP.txt"))
                $files[] = array($year+1, "SP");
            $files[] = array($year, "FA");
        }
        return $files;
    }

	function getClassData($year, $semester, $trad, $campus) {
        if(!$trad) {
            $prefix = "non";
        }
		if(!file_exists($prefix.$year.$semester.".txt")) {
            die("There is no data available for $semester $year");
        }
        $file = fopen($prefix.$year.$semester.".txt", "r");
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
        $classOptions = array();
        foreach($courses as $i=>$sections) {
            if(count($sections) == 1) {
                Schedule::$common[] = $sections[0];
                unset($courses[$i]);
            } else {
                $classOptions[substr($sections[0], 0, -3)] = $sections;
            }
        }
        if(count($courses) == 0) {
            //the schedule still has common classes that need to be validated
            //just because there are no options doesn't mean you can take these classes
            $temp = new Schedule(array());
            $valid = $temp->isValid();
            if($valid === true) {
                return array(array(), array());
            } else {
                return $valid;
            }
        }
        //fix the indices unset messed up
        $courses = array_values($courses);

        $indexes = array_fill(0, count($courses), 0);
        $schedules = array();
        $conflict = null;
        while(true) {
            $classes = array();
            foreach($courses as $i=>$sections) {
                $classes[] = $sections[$indexes[$i]];
            }
            $temp = new Schedule($classes);
            $valid = $temp->isValid();
            if($valid === true) {
                $schedules[] = $temp;
            } else {
                $conflict = $valid;
            }
            for($i = 0; ++$indexes[$i] == count($courses[$i]);) {
                $indexes[$i++] = 0;
                //this exits the loop
                if($i == count($courses)) {
                    break 2;
                }
            }
        }
        if(count($schedules) == 0) {
            return $conflict;
        }

        //find classes that could have had options, but only one works
        $common = array_diff($schedules[0]->getClasses(), Schedule::$common);
        foreach($schedules as $schedule) {
            $common = array_intersect($common, $schedule->getClasses());
            if(empty($common)) {
                break;
            }
        }
        $tmp = array_diff($common, Schedule::$common);
        foreach($tmp as $class) {
            unset($classOptions[substr($class, 0, -3)]);
        }
        Schedule::$common = array_merge(Schedule::$common, $common);
		return array($schedules, $classOptions);
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
		$start1 = $class1->getStartTime();
		$start2 = $class2->getStartTime();
		$end1 = $class1->getEndTime();
		$end2 = $class2->getEndTime();
        //if one of the classes ends before the other one starts, no overlap
        if($end1 < $start2 || $end2 < $start1) {
            return false;
        } else {
            return $class1->getTitle()." conflicts with ".$class2->getTitle();
        }
	}

	function isDayOverlap(Course $class1, Course $class2) {
        return ((int)$class1->getDays() & (int)$class2->getDays()) > 0;
	}

    function isDateOverlap(Course $class1, Course $class2) {
        if($class1->getEndDate() < $class2->getStartDate() || $class2->getEndDate() < $class1->getStartDate())
            return false;
        return true;
    }

	function displaySchedules($schedules, $total) {
		if(is_array($schedules)):?>
            <br>
			<table class="full border">
				<?php if($total != count($schedules)):?>
					<tr><td>Showing <?php echo count($schedules)?> of <?php print $total; ?> possible ways to take your other classes</td></tr>
				<?php else:?>
    				<tr><td>There are <?php echo count($schedules)?> possible ways to take the rest of your classes</td></tr>
				<?php endif;?>
				<?php foreach($schedules as $schedule):?>
					<tr><td style="border:0px;">
                        <?php $schedule->display($total)?>
                    </td></tr>
				<?php endforeach;?>
			</table>
		<?php else:?>
			<font color="red">Sorry, <?php print $schedules ?></font><br>
		<?php endif;
	}
?>