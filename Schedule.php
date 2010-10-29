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
     * Processes and displays a schedule.
     *
     * @author Bion Oren
     * @version 2
     */
    class Schedule {
		public static $common = array();

        /**
         * Creates the javascript to show/hide a set of optional classes.
         *
         * @param $sections ARRAY - List of class sections in this optional list.
         * @param $key STRING - Class ID for these sections.
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
         * @param $classes ARRAY - List of lists of classes.
         * @return VOID
         */
        public static function display(array $classes) {
            $span = (Main::isTraditional())?7:9;
            //make classes show up in a pretty order
//            usort($classes, array("Course", "classSort"));
            print '<table class="full border">';
                print '<tr>';
                    if(Main::isTraditional()) {
                        Schedule::showTraditionalHeaders();
                    } else {
                        Schedule::showNonTraditionalHeaders();
                    }
                print '</tr>';

				$noCommon = true;
				$haveOthers = false;
				foreach($classes as $sections) {
					if(count($sections) == 1) {
						if($noCommon) {
							$noCommon = false;
							print '<tr><td style="border-bottom-color:black;" colspan="'.$span.'">';
								print 'These are the only times you can take these classes:';
							print '</td></tr>';
						}
						print $sections[0]->display()."\n";
						Schedule::$common[] = $sections[0];
					} else {
						$haveOthers = true;
					}
				}

                if($haveOthers) {
                    print '<tr><td style="border-bottom-color:black;" colspan="'.$span.'">';
                        print 'These classes have some options:';
                    print '</td></tr>';

					foreach($classes as $sections) {
						if(count($sections) > 1) {
							$key = current($sections)->getID();
							print "<tr style='cursor:pointer;' onclick='".Schedule::createJSToggle($sections, $key)."'><td><span id='".$key."'>+</span> ".$key."</td><td colspan='".($span-1)."'>".current($sections)->getTitle()." (".count($sections).")</td></tr>\n";
							foreach($sections as $section) {
								print $section->display(true)."\n";
							}
						}
					}
                }
            print '</table>';
            print '<br/>';
            print '<a href="print.php?'.Schedule::getPrintQS(Schedule::$common).'" id="printer" class="print-no">Printer Friendly</a>';
        }

        /**
         * Returns the querystring used to generate a picture of the given classes.
         *
         * @param $classes ARRAY - List of classes to display.
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
    }
?>