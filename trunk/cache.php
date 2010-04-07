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

	function writeClassData($file, $year, $semester, $prefix="non") {
		//get the current class schedule from LeTourneau
		$file .= $year."/".$semester;
		$xml = simplexml_load_file($file);
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
        $file = fopen("temp.txt", "w");
        fwrite($file, $titleLookup[$semester]." ".$year);
		foreach($classes as $class) {
            fwrite($file, "\n".serialize($class));
        }
        fclose($file);
        //seamlessly transition the new data
        rename("temp.txt", $prefix.$year.$semester.".txt");
        return true;
	}

    $files = getFileArray(false);
    $trad = "http://gimme.letu.edu/courseschedule/trad/full/";
    $nontrad = "http://gimme.letu.edu/courseschedule/nontrad/full/";
    for($i = 0; $i < count($files); $i++) {
        $year = $files[$i][0];
        $sem = $files[$i][1];
        writeClassData($trad, $year, $sem, "");
        writeClassData($nontrad, $year, $sem);
    }
?>