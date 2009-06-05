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

    #Div layers are the answer! Just create functions that map days to x position and hours to y position
    require_once("functions.php");
    $classes = explode("&", $_SERVER["QUERY_STRING"]);
    foreach($classes as $key=>$class) {
        $classes[$key] = unserialize(base64_decode($class));
    }
    //add chapel
    $dataArray["course"] = "LETU-1111";
    $dataArray["section"] = "01";
    $dataArray["days"] = 2+8+32;
    $dataArray["times"][0] = "10:50a";
    $dataArray["times"][1] = "11:30a";
    $dataArray["title"] = "Chapel";
    $dataArray["prof"] = "Chaplain Carl";
    $dataArray["curReg"] = "0";
    $dataArray["maxReg"] = "10000";
    $classes["chapel"] = new Course($dataArray);

    //surely we'll never have class on Sunday...
    $days = array("Monday", "Tuesday", "Wednesday", "Thursay", "Friday", "Saturday");
    //do you want to teach at some other obscure hour?
    $hours = array(7,8,9,10,11,12,1,2,3,4,5,6,7,8,9,10);

    print '<table border="1" cellspacing="0"><tr><td></td>';
    foreach($days as $day) {
        print '<td width="100">'.$day.'</td>';
    }
    print '</tr>';

    //print the shell of the schedule sheet
    foreach($hours as $hour) {
        print '<tr><td valign="top" height="50">'.$hour.':00</td>';
        foreach($days as $dayKey=>$day) {
            print '<td>&nbsp;</td>';
        }
        print '</tr>';
    }

    //$day integer from 0-6
    function xFromDay($day) {
        $offset = -53;
        return $day*(100+4)+$offset;
    }

    //$time is ##:##x
    function yFromTime($time) {
        $offset = -317;
        //print "time = $time<br>";
        return $time*50+$offset;
    }

    $nums = array(1, 2, 4, 8, 16, 32, 64);
    foreach($classes as $class) {
        for($i = 0; $i < count($nums); $i++) {
            if($class->getDays() & $nums[$i]) {
                $start = yFromTime($class->getStartTime());
                $end = yFromTime($class->getEndTime());
                //print $class->getStartTime()." - ".$class->getEndTime()."<br>";
                //print "$start - $end<br>";
                print '<div style="position:absolute; top:'.$start.'; left:'.xFromDay($i).'; width:100; height:'.($end-$start).'; overflow:visible;';
                print 'background-color:yellow;">';
                print str_replace("/", " ", $class->getTitle()."<br>");
                //print $class->displayTime($class->getStartTime())." - ".$class->displayTime($class->getEndTime())."<br>";
                print '</div>';
            }
        }
    }
?>
