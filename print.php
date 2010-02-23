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

    #Div layers are the answer! Just create functions that map days to x position and hours to y position
    $tmp = explode("&", $_SERVER["QUERY_STRING"]);
    $classes = array();
    foreach($tmp as $class) {
        $classes[] = explode("::", $class);
    }
    //add chapel
    $tmp = array();
    $tmp[] = 2+8+32;
    $tmp[] = "10.83333333333";
    $tmp[] = "11.5";
    $tmp[] = "Chapel";
    $classes[] = $tmp;

    //surely we'll never have class on Sunday...
    $days = array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
    //do you want to teach at some other obscure hour?
    $hours = array(7,8,9,10,11,12,1,2,3,4,5,6,7,8,9,10);

    //print the shell of the schedule sheet
    print '<table border="1" cellspacing="0"><tr><td></td>';
    foreach($days as $day) {
        print '<td width="100">'.$day.'</td>';
    }
    print '</tr>';

    foreach($hours as $hour) {
        print '<tr><td valign="top" height="50">'.$hour.':00</td>';
        foreach($days as $dayKey=>$day) {
            print '<td>&nbsp;</td>';
        }
        print '</tr>';
    }

    //$day integer from 0-6
    function xFromDay($day) {
        $offset = -54;
        return $day*(100+4)+$offset;
    }

    //$time is ##:##x
    function yFromTime($time) {
        $offset = -317;
        return $time*50+$offset;
    }

    $nums = array(1, 2, 4, 8, 16, 32, 64);
    foreach($classes as $class) {
        for($i = 0; $i < count($nums); $i++) {
            if($class[0] & $nums[$i]) {
                $start = yFromTime($class[1]);
                $end = yFromTime($class[2]);
                print '<div style="position:absolute; top:'.$start.'; left:'.xFromDay($i).'; width:98; height:'.($end-$start).'; overflow:visible;';
                print 'background-color:yellow; border-color:rgb(43, 175, 160); border-style:solid; border-width:2px;">';
                print '<span style=\'font-family: "Lucida Grande", "Lucida Sans Unicode", Verdana, Arial, Helvetica, sans-serif; font-size: 0.8em;\'>';
                print str_replace("/", " ", urldecode($class[3])."<br>");
                print "</span>";
                print '</div>';
            }
        }
    }
?>
