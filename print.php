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
    error_reporting(E_ALL);

    require_once("functions.php");
    require_once("Meeting.php");

    /**
     * Handles display of a class schedule.
     *
     * All input should be in the query string. This class expects the following parameters:
     * sem=YYYYXX where YYYY is the 4 digit year and XX is a 2 letter semester code (one of SP, SU, or FA).
     * [trad=[trad/non]] (Default trad)
     * classes=LIST where LIST is a ~ seperated list with each element in the following format:
     *      1::2::3::4::5 where each number corresponds as follows:
     *      1: INTEGER - Bit string of days this class occurs on (see $DAY_NUMBERS below)
     *      2: FLOAT - 24 floating point representation of the class' start time (hours + minutes/60)
     *      3: FLOAT - class' end time (see formatting notes for 2)
     *      4: STRING - location of the class
     *      5: STRING - Name of the class
     *
     * @author Bion Oren
     * @version 1.0
     */
    class SchedulePrinter {
        /** ARRAY bits corresponding to each day of the week. */
        public static $DAY_NUMBERS = array(1, 2, 4, 8, 16, 32, 64);
        /** ARRAY names for each day of the week. */
        public static $DAYS = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");

        //IMAGE RELATED CONSTANTS

        /** INTEGER Image width. */
        protected static $width = 670;
        /** INTEGER Image height. */
        protected static $height = 880;
        /** INTEGER X offset for the schedule (leaves room for the hour row headers). */
        protected static $offsetX = 50;
        /** INTEGER Y offset for the schedule (leaves room for the day column headers). */
        protected static $offsetY = 25;
        /** INTEGER Default start day (Monday). */
        protected static $defaultStartDay = 1;
        /** INTEGER Default number of days (5 = Monday-Friday default). */
        protected static $defaultNumDays = 5;

        //INFORMATION SPECIFIC TO THIS SCHEDULE

        /**
         * INTEGER The start day for this schedule.
         * @see $defaultStartDay
         */
        protected $startDay;
        /**
         * INTEGER The number of days in this schedule.
         * @see $defaultNumDays
         */
        protected $numDays;
        /** INTEGER The number (24 hour clock) of the first hour in this schedule. */
        protected $startTime;
        /** INTEGER The number (24 hour clock) of the last hour in this schedule. */
        protected $endTime;
        /** INTEGER Width of a day in pixels. */
        protected $dayWidth;
        /** INTEGER Height of an hour in pixels. */
        protected $hourHeight;
        /** ARRAY List of classes in this schedule. */
        protected $classes = array();

        //IMAGE INFORMATION FOR THIS SCHEDULE

        /** STRING Location of a font to use. Tahoma is from the open source WINE project. */
        protected $font = "layout/tahoma.ttf";
        /** RESOURCE Default background color. */
        protected $background;
        /** RESOURCE Default text color. */
    	protected $foreground;
        /** RESOURCE Default background color for class blocks. */
        protected $classBackground;
        /** RESOURCE Internal image resource. */
        protected $img;

        /**
         * Initializes color and image information and processes arguments from the query string.
         */
        public function __construct() {
            $this->setImage(imagecreate($this::$width, $this::$height));
            $this->background = imagecolorallocate($this->img, 255, 255, 255);
            $this->foreground = imagecolorallocate($this->img, 0, 0, 0);
            $this->classBackground = imagecolorallocate($this->img, 253, 255, 79);

            $tmp = explode("~", urldecode($_REQUEST["classes"]));
            foreach($tmp as $class) {
                if(!empty($class)) {
                    $tmp = explode("::", $class);
                    if($tmp[1] != "TBA" && $tmp[1] > 0 && $tmp[0] > 0) {
                        $this->classes[] = $tmp;
                    }
                }
            }
            if(substr($_REQUEST["sem"], -2) != "SU" && $_REQUEST["trad"] != "non") {
                //add chapel
                $tmp = array();
                $tmp[] = 2+8+32;
                $tmp[] = 10.83333333333;
                $tmp[] = 11.5;
                $tmp[] = "Belcher";
                $tmp[] = "Chapel";
                $this->classes[] = $tmp;
            }

            if(empty($this->classes)) {
                die();
            }

            //find the first and last class
            $this->startTime = 24;
            $this->endTime = 0;
            $this->startDay = $this::$defaultStartDay;
            $this->numDays = $this::$defaultNumDays;
            foreach($this->classes as $class) {
                if($class[1] < $this->startTime) {
                    $this->startTime = floor($class[1]);
                }
                if($class[2] > $this->endTime) {
                    $this->endTime = ceil($class[2]);
                }
                if($class[0] & 1) {
                    //class on Sunday
                    $this->numDays++;
                    $this->startDay = 0;
                }
                if($class[0] & 64) {
                    //class on Saturday
                    $this->numDays++;
                }
            }
        }

        /**
         * Displays the current schedule image.
         *
         * @return VOID
         */
        public function display() {
            header('Content-type: image/gif');
            imagegif($this->img);
        }

        /**
         * Draws an individual class.
         *
         * @param INTEGER $classDays Bit string of days this class occurs on (see $DAY_NUMBERS below).
         * @param FLOAT $startTime 24 floating point representation of the class' start time (hours + minutes/60).
         * @param FLOAT $endTime class' end time (see formatting notes for 2).
         * @param STRING $location location of the class.
         * @param STRING $title Name of the class.
         * @return VOID
         */
        function drawClass($classDays, $startTime, $endTime, $location, $title) {
            for($i = 1; $i < count(SchedulePrinter::$DAY_NUMBERS); $i++) {
                if($classDays & SchedulePrinter::$DAY_NUMBERS[$i]) {
                    $start = $startTime-$this->startTime;
                    $end = $endTime-$this->startTime;
                    imagefilledrectangle($this->img, $this::$offsetX+2+$this->dayWidth*($i-1), $this::$offsetY+5+$start*$this->hourHeight, $this::$offsetX-3+$this->dayWidth*$i, $this::$offsetY-5+$end*$this->hourHeight, $this->classBackground);
                    imagefilledrectangle($this->img, $this::$offsetX+2+5+$this->dayWidth*($i-1), $this::$offsetY+$start*$this->hourHeight, $this::$offsetX-3-5+$this->dayWidth*$i, $this::$offsetY+$end*$this->hourHeight, $this->classBackground);
                    //rounded edges
                    //ul
                    imagefilledellipse($this->img, $this::$offsetX+1+6+$this->dayWidth*($i-1), $this::$offsetY+5+$start*$this->hourHeight, 10, 10, $this->classBackground);
                    //ll
                    imagefilledellipse($this->img, $this::$offsetX+1+6+$this->dayWidth*($i-1), $this::$offsetY-5+$end*$this->hourHeight, 10, 10, $this->classBackground);
                    //ur
                    imagefilledellipse($this->img, $this::$offsetX+1-9+$this->dayWidth*($i), $this::$offsetY+5+$start*$this->hourHeight, 10, 10, $this->classBackground);
                    //lr
                    imagefilledellipse($this->img, $this::$offsetX+1-9+$this->dayWidth*($i), $this::$offsetY-5+$end*$this->hourHeight, 10, 10, $this->classBackground);

                    $pos = imagettftext($this->img, 11, 0, $this::$offsetX+4+$this->dayWidth*($i-1), $this::$offsetY+16+$start*$this->hourHeight, $this->foreground, $this->font, $this->wrap(11, 0, $title, $this->dayWidth));
                    $tmp = $pos[1]-$pos[7]+16;
                    imagettftext($this->img, 10, 0, $this::$offsetX+2+$this->dayWidth*($i-1), $this::$offsetY+$tmp+$start*$this->hourHeight, $this->foreground, $this->font, $location);
                    $tmp += 16;
                    imagettftext($this->img, 10, 0, $this::$offsetX+2+$this->dayWidth*($i-1), $this::$offsetY+$tmp+$start*$this->hourHeight, $this->foreground, $this->font, Meeting::displayTime($startTime)." - ".Meeting::displayTime($endTime));
                }
            }
        }

        /**
         * Draws all of the classes in this schedule.
         *
         * @return VOID
         */
        public function drawClasses() {
            foreach($this->classes as $class) {
                $this->drawClass($class[0], $class[1], $class[2], $class[3], $class[4]);
            }
        }

        /**
         * Draws the hour and day headers and bounding boxes for the schedule.
         *
         * @return VOID
         */
        function drawFrame() {
            imagesetthickness($this->img, 2);
            //border
            imagerectangle($this->img, 0, 1, $this::$width-1, $this::$height-2, $this->foreground);

            imagesetthickness($this->img, 1);
            $this->dayWidth = ($this::$width-$this::$offsetX)/$this->numDays;
            for($i = 0; $i < $this->numDays; $i++) {
                $x = $this::$offsetX+$this->dayWidth*$i;
                imagerectangle($this->img, $x, 1, $x+$this->dayWidth, $this::$height-1, $this->foreground);
                imagettftext($this->img, 14, 0, $x+16, 20, $this->foreground, $this->font, $this::$DAYS[$i+$this->startDay]);
            }

            //hour headers
            $startHour = $this->startTime-1;
            $numHours = $this->endTime-$this->startTime+1;
            $this->hourHeight = (SchedulePrinter::$height - $this::$offsetY)/$numHours;
            for($i = 0; $i < $numHours; $i++) {
                $y = $this::$offsetY+$this->hourHeight*$i;
                imagerectangle($this->img, 0, $y, $this::$width-1, $y+$this->hourHeight, $this->foreground);
                imagettftext($this->img, 12, 0, 4, $y+17, $this->foreground, $this->font, (($i+$startHour)%12+1).":00");
            }
        }

        /**
         * Sets the current image resource, cleaning up the old one.
         *
         * @param RESOURCE $img The new image to set.
         * @return VOID
         */
        protected function setImage($img) {
            if($this->img) {
                imagedestroy($this->img);
            }
            $this->img = $img;
            if($img) {
                imagefill($img, 0, 0, $this->background);
            }
        }

        /**
         * Wraps text.
         *
         * @param INTEGER $fontSize The size of the font.
         * @param INTEGER $angle The angle of the text from vertical.
         * @param STRING $string The string to wrap.
         * @param INTEGER $width The width of the container.
         * @return STRING Text wrapped with newlines.
         */
        function wrap($fontSize, $angle, $string, $width){
            $ret = "";
            $arr = explode(' ', str_replace("/", "/ ", htmlspecialchars_decode($string)));
            foreach($arr as $word) {
                $testbox = imagettfbbox($fontSize, $angle, $this->font, $ret.' '.$word);
                if ($testbox[2] > $width) {
                    $ret .= "\n".$word;
                } else {
                    $ret .= ' '.$word;
                }
            }
            return substr($ret, 1);
        }

        /**
         * Cleans up the image associated with this class and writes out a 1x1 placeholder with the current background color.
         *
         * @return VOID
         */
        public function __destruct() {
            $this->setImage(imagecreate(1,1));
            $this->display();
            $this->setImage(null);
        }
    }

    $printer = new SchedulePrinter();
    $printer->drawFrame();
    $printer->drawClasses();
    $printer->display();
?>
