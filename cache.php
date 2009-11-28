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

    function func($val) {
        return trim(substr($val, 1, strlen($val)-3));
    }

    function fetchCurrentSemester($file, $year=2009, $semester="FA", $prefix="non") {
        //get the current class schedule from LeTourneau
		if(!empty($year) && !empty($semester)) {
			$file .= "?target_term=".$year."%7C".$semester;
		}
		$courseSchedule = file_get_contents($file);

        $matches = array();
        preg_match("/name=\"target_term\".+?<option[^\>]*?SELECTED.*?>([^\<]+?)<\/option>/is", $courseSchedule, $matches);
        return $matches[1];
    }

	function writeClassData($file, $title, $year, $semester, $prefix="non") {
		//get the current class schedule from LeTourneau
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
		if($prefix == "non") {
            $keys = Course::$NON_KEYS;
        } else {
            $keys = Course::$KEYS;
        }
		$classData = array();
		foreach($classes as $val) {
            $classInfo = array();
			//break up the class into it's bits of information
			$sections = array();
			preg_match_all("/\>[^\<]+?<\//is", $val, $sections);
            $sections = $sections[0];

			//evaluate and store each portion of a class' information
            $sections = array_map(func, $sections);
            if(count($keys) != count($sections)) {
                $class = $classData[count($classData)-1];
                $sections = array_pad($sections, -count($keys), 0);
                $sections = array_combine($keys, $sections);
            } else {
                $sections = array_combine($keys, $sections);
            }
            $tmp = str_split($sections["days"]);
            $temp = 0;
            for($i = 0; $i < count($tmp); $i++) {
                if($tmp[$i] != "-")
                    $temp += pow(2, $i);
            }
            $sections["days"] = $temp;
            $sections["times"] = explode("-", $sections["times"]);

            if(empty($sections["prof"])) {
                $classInfo = $class->mergeLabWithClass($sections);
            } else {
                $classInfo = $sections;
            }
            $class = new Course($classInfo);
            $classData[] = $class;
		}

        $file = fopen("temp.txt", "w");
        fwrite($file, $title."\n");
		for($i = 0; $i < count($classData); $i++) {
            if(is_object($class)) {
                fwrite($file, serialize($classData[$i]));
                if($i+1 < count($classData))
                    fwrite($file, "\n");
            }
        }
        fclose($file);
        //seamlessly transition the new data
        rename("temp.txt", $prefix.$year.$semester.".txt");
//        rename("temp.txt", "/Library/WebServer/Documents/LUSASE/".$year.$semester.".txt");
	}

    $files = getFileArray(false);
    $lastSemester = "";
    $trad = "http://www.letu.edu/academics/course-sched/index.html";
    $nontrad = "http://www.letu.edu/academics/course-sched/nontrad.html";
    for($i = 0; $i < count($files); $i++) {
        $year = $files[$i][0];
        $sem = $files[$i][1];
        $semester = fetchCurrentSemester($trad, $year, $sem, "");
        if($semester == $lastSemester) {
            break;
        } else {
            $lastSemester = $semester;
            writeClassData($trad, $semester, $year, $sem, "");
        }
        $semester = fetchCurrentSemester($nontrad, $year, $sem);
        writeClassData($nontrad, $semester, $year, $sem);
    }
?>