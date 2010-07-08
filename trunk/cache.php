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

    error_reporting(E_ALL & E_STRICT);
    date_default_timezone_set("America/Chicago");
    require_once("functions.php");
    require_once("Course.php");

	function getCache($file) {
        $name = "cache/".md5($file).".tmp";
        if(file_exists($name)) {
            return file_get_contents($name);
        } else {
            $ret = file_get_contents($file);
            file_put_contents($name, $ret);
            return $ret;
        }
    }

    /**
     * Writes cash data for all courses in the given semester and year using data from the
     * provided root file path.
     *
     * @param STRING $file Root path to the xml class information.
     * @param INTEGER $year The year to cash.
     * @param STRING $semester One of ('SP', 'SU', 'FA') for Spring, Summer, and Fall semesters.
     * @param STRING $prefix Optional prefix ('non' for non-traditional classes).
     */
	function writeClassData($file, $year, $semester, $prefix="non") {
		//get the current class schedule from LeTourneau
		$file .= $year."/".$semester;
//		$xml = simplexml_load_file($file);
		$xml = simplexml_load_string(getCache($file)); //TODO THIS IS FOR DEBUGGING ONLY!
        if($xml->count() == 0 || $xml === false) {
            //there's no data here, or there was an error
            return false;
        }

        $classes = array();
        foreach($xml as $class) {
            if($class->{"sectionnumber"} == "HD") {
                continue;
            }
            $classes[] = new Course($class);
        }

        $titleLookup = array("SP"=>"Spring", "SU"=>"Summer", "FA"=>"Fall");
        $file = fopen("cache/temp.txt", "w");
        fwrite($file, $titleLookup[$semester]." ".$year);
		foreach($classes as $class) {
            fwrite($file, "\n".serialize($class));
        }
        fclose($file);
        //seamlessly transition the new data
        rename("cache/temp.txt", "cache/".$prefix.$year.$semester.".txt");
        return true;
	}

    $files = getFileArray(false);
    $trad = "http://gimme.letu.edu/courseschedule/trad/full/";
    $nontrad = "http://gimme.letu.edu/courseschedule/sgps/full/";
    foreach($files as $file) {
        $year = $file[0];
        $sem = $file[1];
        writeClassData($trad, $year, $sem, "");
        writeClassData($nontrad, $year, $sem);
    }
?>