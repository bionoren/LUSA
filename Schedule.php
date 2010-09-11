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

    /**
     * Stores information for a schedule.
     *
     * @author Bion Oren
     * @version 1.5
     */
    class Schedule {
        /** ARRAY Array of classes that are common to all possible schedules. */
        public static $common = array();

        /** ARRAY Array of all classes in this classes. */
        protected $classes = array();
        /** ARRAY Array of classes that are unique to this schedule (ie not common). */
        protected $uniqueClasses = array();
        /** MIXED False if this schedule is valid, otherwise a string with the error message(s). */
        protected $valid = false;

        /**
         * Constructs a new schedule with the given classes. By default, the list of common classes
         * is included.
         *
         * @param ARRAY $classes Optional list of classes to add.
         */
        public function __construct(array $classes=null) {
            $this->classes = Schedule::$common;
            if(!empty($classes)) {
                foreach($classes as $class) {
                    $this->addClass($class, false);
                }
                $this->validate();
            }
        }

		/**
		 * Returns a new schedule with the common classes as though it were a distinct schedule.
		 *
		 * @return SCHEDULE Common schedule.
		 */
		public static function commonSchedule() {
			$common = new Schedule();
			$common->uniqueClasses = $common->classes;
			return $common;
		}

        /**
         * Adds a class and optionally validates the schedule.
         *
         * @param COURSE $class Class to add.
         * @param BOOLEAN $validate Validates the schedule if true.
         * @return MIXED True if not validated or valid, error string on error.
         */
        public function addClass(Course $class, $validate=true) {
            $this->classes[] = $class;
            $this->uniqueClasses[] = $class;
            if($validate) {
                return $this->isValid();
            }
            return true;
        }

        /**
         * Creates the javascript to show/hide a set of optional classes.
         *
         * @param ARRAY $sections List of class sections in this optional list.
         * @param STRING $key Class ID for these sections.
         * @return STRING Javascript text.
         */
        protected static function createJSToggle(array $sections, $key) {
            $ret = 'state = "visible";';
            $ret .= 'if($("'.current($sections)->getUID().'0").style.visibility == "visible") { state = "collapse"; }';
            $ret .= 'if(state == "visible") { $("'.$key.'").innerHTML = "-"; } else { $("'.$key.'").innerHTML = "+"; }';
            foreach($sections as $section) {
				for($i = 0; $i < $section->getNumMeetings(); $i++) {
	                $ret .= '$("'.$section->getUID().$i.'").style.visibility = state;';
				}
            }
            return $ret;
        }

        /**
         * Displays the given schedules.
         *
         * @param ARRAY $schedules List of schedules.
         * @return VOID
         */
        public static function display(array $schedules) {
            $optionClasses = Schedule::getOptionClasses($schedules);
            $span = (Main::isTraditional())?7:9;
            //make classes show up in a pretty order
//            usort(Schedule::$common, array("Course", "classSort"));
            print '<table class="full border">';
                print '<tr>';
                    if(Main::isTraditional()) {
                        Schedule::showTraditionalHeaders();
                    } else {
                        Schedule::showNonTraditionalHeaders();
                    }
                print '</tr>';
                if(count(Schedule::$common) > 0) {
                    print '<tr><td style="border-bottom-color:black;" colspan="'.$span.'">';
                        print 'These are the only times you can take these classes:';
                    print '</td></tr>';
                }
                foreach(Schedule::$common as $class) {
                    print $class->display()."\n";
                }

                if(!empty($optionClasses)) {
                    print '<tr><td style="border-bottom-color:black;" colspan="'.$span.'">';
                        print 'These classes have some options:';
                    print '</td></tr>';
                }
                foreach($optionClasses as $key=>$sections) {
                    print "<tr style='cursor:pointer;' onclick='".Schedule::createJSToggle($sections, $key)."'><td><span id='".$key."'>+</span> ".$key."</td><td colspan='".($span-1)."'>".current($sections)->getTitle()." (".count($sections).")</td></tr>\n";
                    foreach($sections as $section) {
                        print $section->display(true)."\n";
                    }
                }
            print '</table>';
            print '<br/>';
            print '<a href="print.php?'.Schedule::getPrintQS(Schedule::$common).'" id="printer">Printer Friendly</a>';
        }

        /**
         * Returns this schedule's validation state.
         *
         * @return MIXED True if this schedule is valid, otherwise an error string.
         */
        public function isValid() {
            return $this->valid;
        }

        /**
         * Returns an array of all the classes in this schedule.
         *
         * @return ARRAY Schedule classes.
         */
        public function getClasses() {
            return $this->classes;
        }

        /**
         * Returns an array of all the classes in the given schedules that are optional.
         *
         * @param ARRAY $schedules List of schedules.
         * @return ARRAY List of optional classes of the form $ret[classID][section] = class.
         */
        protected static function getOptionClasses(array $schedules) {
            $classOptions = array();
            reset($schedules);
			while($schedule = next($schedules)) {
                foreach($schedule->getUniqueClasses() as $class) {
                    if(!in_array($class, Schedule::$common)) {
                        $classOptions[$class->getID()][$class->getUID()] = $class;
                    }
                }
            }
            return $classOptions;
        }

        /**
         * Returns the querystring used to generate a picture of the given classes.
         *
         * @param ARRAY $classes List of classes to display.
         * @return STRING Querystring for display.
         * @see print.php
         */
        public static function getPrintQS($classes=array()) {
            $ret = 'sem='.Main::getSemester().'&amp;trad='.Main::isTraditional().'&amp;classes=';
            $tmp = array();
            foreach($classes as $class) {
                $tmp[] = $class->getPrintQS();
            }
            $ret .= implode("~", $tmp);
            $ret = str_replace(" ", "%20", $ret);
            return $ret;
        }

        /**
         * Returns an array of the classe that are unique to this schedule (not common).
         *
         * @return ARRAY
         * @see $uniqueClasses
         */
        public function getUniqueClasses() {
            return $this->uniqueClasses;
        }

        /**
         * Prints headers for non-traditional classes.
         *
         * @return VOID
         */
        protected static function showNonTraditionalHeaders() {
            ?>
            <th colspan="2" id="classHeader">Class</th>
			<th id="sectionHeader">Section</th>
			<th id="campusHeader">Campus</th>
            <th id="profHeader">Prof</th>
            <th id="dateHeader">Dates</th>
            <th id="dayHeader">Days</th>
            <th id="timeHeader">Time</th>
			<th id="registeredHeader">Registered/Size</th>
            <?php
        }

        /**
         * Prints headers for traditional classes.
         *
         * @return VOID
         */
        protected static function showTraditionalHeaders() {
            ?>
            <th colspan="2" id="classHeader">Class</th>
			<th id="sectionHeader">Section</th>
            <th id="profHeader">Prof</th>
            <th id="dayHeader">Days</th>
            <th id="timeHeader">Time</th>
			<th id="registeredHeader">Registered/Size</th>
            <?php
        }

        /**
         * Validates the schedule.
         *
         * @return MIXED
         * @see isValid
         */
        public function validate() {
            //eliminate schedules that have overlaps
			$sched = array($this);
			foreach($this->classes as $class) {
				$this->valid .= Schedule::validateClassSections($sched, array($class));
			}
            //return all the conflicts together
            return $this->isValid();
        }

        /**
         * Validates this schedule's unique class set with the addition of the given class
         * (does NOT add the class!)
         *
         * @param COURSE $class1 Class to validate with this schedule.
         * @return MIXED False if valid, String if there was a conflict.
         * @see isValid
         */
        public function validateClass(Course $class1) {
            //eliminate schedules that have overlaps
            //you can always take special classes
            if($class1->isSpecial()) {
                return false;
            }
			$ret = null;
            //check this class against this schedule's unique set of classes
            foreach($this->uniqueClasses as $class2) {
                if(!$class1->validateClasses($class2)) {
					$ret .= $class1->getLabel()." (conflicts with ".$class2->getTitle().")";
				}
            }

			return $ret;
        }

		/**
		 * Finds if at least one section will work with at least one existing schedule
		 *
		 * @param ARRAY $schedules List of schedules to validate against
		 * @param ARRAY $sections List of course sections to validate
		 * @return MIXED false if valid, String if there was a conflict.
		 */
		public static function validateClassSections(array $schedules, array $sections) {
			$invalid = false;
			$common = Schedule::commonSchedule();
			//check the common classes first. If it works with the common classes,
			//it will probably work with the rest too
			foreach($sections as $section) {
				$invalid = $common->validateClass($section);
				if(!$invalid) {
					break;
				}
			}
			if($invalid) {
				return $invalid;
			}
			//check against all the other schedule options
			foreach($schedules as $schedule) {
				foreach($sections as $section) {
					$invalid = $schedule->validateClass($section);
					if(!$invalid) {
						return false;
					}
				}
			}
			return $invalid;
		}
    }
?>