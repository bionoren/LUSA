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

	date_default_timezone_set("America/Chicago");

    require_once("functions.php");
    require_once("Course.php");

    function wrap($fontSize, $angle, $fontFace, $string, $width){
        $ret = "";
        $arr = explode(' ', $string);
        foreach($arr as $word) {
            $testbox = imagettfbbox($fontSize, $angle, $fontFace, $ret.' '.$word);
            if ($testbox[2] > $width) {
                $ret .= "\n".$word;
            } else {
                $ret .= ' '.$word;
            }
        }
        return substr($ret, 1);
    }

    $tmp = explode("~", $_REQUEST["classes"]);
    $classes = array();
    foreach($tmp as $class) {
        if(!empty($class)) {
            $tmp = explode("::", $class);
            if($tmp[1] != "TBA") {
                $classes[] = $tmp;
            }
        }
    }
	if(substr($_REQUEST["sem"], -2) != "SU") {
		//add chapel
		$tmp = array();
		$tmp[] = 2+8+32;
		$tmp[] = 10.83333333333;
		$tmp[] = 11.5;
		$tmp[] = "Chapel";
		$classes[] = $tmp;
	}
	if(empty($classes)) {
		die();
	}

    //find the first and last class
    $min = 24;
    $max = 0;
    $startDay = 1;
    $numDays = 5;
    foreach($classes as $class) {
        if($class[1] < $min) {
            $min = floor($class[1]);
        }
        if($class[2] > $max) {
            $max = ceil($class[2]);
        }
        if($class[0] & 1) {
            //class on Sunday
            $numDays++;
        }
        if($class[0] & 64) {
            //class on Saturday
            $numDays++;
        }
    }

    header('Content-type: image/gif');
    $imgWidth = 670;
    $imgHeight = 880;
    //taken from the WINE project
    $font = "layout/tahoma.ttf";
    $img = imagecreate($imgWidth, $imgHeight);
    $white = imagecolorallocate($img, 255, 255, 255);
    imagefill($img, 0, 0, $white);
    $black = imagecolorallocate($img, 0, 0, 0);

    imagesetthickness($img, 2);
    //border
    imagerectangle($img, 0, 1, $imgWidth-1, $imgHeight-2, $black);

    imagesetthickness($img, 1);
    $offsetX = 50;
    $incX = ($imgWidth-$offsetX)/$numDays;
    //day headers
    $days = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
    for($i = 0; $i < $numDays; $i++) {
        imagerectangle($img, $offsetX+$incX*$i, 1, $offsetX+$incX*($i+1), $imgHeight-1, $black);
        imagettftext($img, 14, 0, $offsetX+16+$incX*$i, 20, $black, $font, $days[$i+$startDay]);
    }

    //hour headers
    $offsetY = 25;
    $startHour = $min-1;
    $numHours = $max-$min+1;
    $incY = ($imgHeight - $offsetY)/$numHours;
    for($i = 0; $i < $numHours; $i++) {
        imagerectangle($img, 0, $offsetY+$incY*$i, $imgWidth-1, $offsetY+$incY*($i+1), $black);
        imagettftext($img, 12, 0, 4, $offsetY+17+$incY*$i, $black, $font, (($i+$startHour)%12+1).":00");
    }

    //draw the classes
    $nums = array(1, 2, 4, 8, 16, 32, 64);
    $bgcolor = imagecolorallocate($img, 253, 255, 79);
    $offset = -$startHour-1;
    foreach($classes as $class) {
        for($i = 1; $i < count($nums); $i++) {
            if($class[0] & $nums[$i]) {
                $start = $class[1]+$offset;
                $end = $class[2]+$offset;
                imagefilledrectangle($img, $offsetX+2+$incX*($i-1), $offsetY+5+$start*$incY, $offsetX-3+$incX*$i, $offsetY-5+$end*$incY, $bgcolor);
                imagefilledrectangle($img, $offsetX+2+5+$incX*($i-1), $offsetY+$start*$incY, $offsetX-3-5+$incX*$i, $offsetY+$end*$incY, $bgcolor);
                //dumb rounded edges
                //ul
                imagefilledellipse($img, $offsetX+1+6+$incX*($i-1), $offsetY+5+$start*$incY, 10, 10, $bgcolor);
                //ll
                imagefilledellipse($img, $offsetX+1+6+$incX*($i-1), $offsetY-5+$end*$incY, 10, 10, $bgcolor);
                //ur
                imagefilledellipse($img, $offsetX+1-9+$incX*($i), $offsetY+5+$start*$incY, 10, 10, $bgcolor);
                //lr
                imagefilledellipse($img, $offsetX+1-9+$incX*($i), $offsetY-5+$end*$incY, 10, 10, $bgcolor);

                $pos = imagettftext($img, 11, 0, $offsetX+4+$incX*($i-1), $offsetY+16+$start*$incY, $black, $font, wrap(11, 0, $font, str_replace("/", "/ ", urldecode($class[3])), $incX));
                $tmp = $pos[1]-$pos[7]+16;
                imagettftext($img, 10, 0, $offsetX+2+$incX*($i-1), $offsetY+$tmp+$start*$incY, $black, $font, Course::displayTime($class[1])." - ".Course::displayTime($class[2]));
            }
        }
    }

    imagegif($img);
    imagedestroy($img);
?>
