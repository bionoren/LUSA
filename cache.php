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

    require_once("functions.php");

    function fetchCurrentSemester($year=2009, $semester="FA") {
        //get the current class schedule from LeTourneau
		$file = "http://www.letu.edu/academics/course-sched/index.html";
		if(!empty($year) && !empty($semester)) {
			$file .= "?target_term=".$year."%7C".$semester;
		}
		$courseSchedule = file_get_contents($file);

        $matches = array();
        preg_match("/name=\"target_term\".+?<option[^\>]*?SELECTED.*?>([^\<]+?)<\/option>/is", $courseSchedule, $matches);
        return $matches[1];
    }

	function writeClassData($title, $year, $semester) {
		//get the current class schedule from LeTourneau
		$file = "http://www.letu.edu/academics/course-sched/index.html";
		if(!empty($year) && !empty($semester)) {
			$file .= "?target_term=".$year."%7C".$semester;
		}
		$courseSchedule = file_get_contents($file);

		//eliminate all the (useless) search and legend junk at the top
		$startOfSchedule = stripos($courseSchedule, "<p>");
		$courseSchedule = substr($courseSchedule, $startOfSchedule);

		//remove the column titles and surrounding table definition
		$courseSchedule = preg_replace("/\<tr\>.*?\<\/tr\>/is", "", $courseSchedule, 1);
		$courseSchedule = preg_replace("/\<p\>\<table.*?\>\<tr.*?\>/is", "", $courseSchedule);
		$courseSchedule = preg_replace("/\<\/tr\>\<\/table.*/is", "", $courseSchedule);

		//parse each class into an array
		//seperate out each class
		$classes = preg_split("/\<\/tr\>\<tr.*?\>/is", $courseSchedule);
		//type: lb = lab, lc = class, ol = online
		$keys = Course::$KEYS;
		$classData = array();
		foreach($classes as $val) {
            $classInfo = array();
			//break up the class into it's bits of information
			$sections = array();
			preg_match_all("/\>[^\<]+?<\//is", $val, $sections);
            $sections = $sections[0];

			//evaluate and store each portion of a class' information
			foreach($sections as $key2=>$val2) {
				if(count($sections) < 11) { //handle labs
					$key2 += 12-count($sections);
				}
				$val2 = trim(substr($val2, 1, strlen($val2)-3));
				if($key2 == 8) {
					$tmp = str_split($val2);
                    $temp = 0;
					for($i = 0; $i < count($tmp); $i++) {
                        if($tmp[$i] != "-")
    						$temp += pow(2, $i);
					}
					$val2 = $temp;
				}
				if($key2 == 9) {
					$val2 = explode("-", $val2);
				}
				$classInfo[$keys[$key2]] = $val2;
			}
            $class = new Course($classInfo);

            //handle labs
			if(count($sections) < 11) {
                //merge this lab into the last class listed
                $class->mergeLabWithClass($classData[count($classData)-1]);
			}

            //classes with labs can get duplicated. Remove the duplicates.
            if(count($classData) > 1 && $class->equal($classData[count($classData)-2]))
                $classData[count($classData)-2] = null;

            $classData[] = $class;
		}

        $file = fopen("temp.txt", "w");
        fwrite($file, $title."\n");
		for($i = 0; $i < count($classData); $i++) {
            if(is_object($class)) {
                fwrite($file, implode("$$", $classData[$i]->export()));
                if($i+1 < count($classData))
                    fwrite($file, "\n");
            }
        }
        fclose($file);
        //seamlessly transition the new data
        rename("temp.txt", $year.$semester.".txt");
//        rename("temp.txt", "/Library/WebServer/Documents/LUSASE/".$year.$semester.".txt");
	}

    $files = getFileArray(false);
    $lastSemester = "";
    for($i = 0; $i < count($files); $i++) {
        $year = $files[$i][0];
        $sem = $files[$i][1];
        $semester = fetchCurrentSemester($year, $sem);
        if($semester == $lastSemester) {
            break;
        } else {
            $lastSemester = $semester;
            writeClassData($semester, $year, $sem);
        }
    }
?>