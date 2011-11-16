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

	require_once($path."Object.php");
    require_once($path."Course.php");
    require_once($path."functions.php");

    /**
     * Handles processing of the main page.
     *
     * Provides user input to other classes that need it.
     *
     * @author Bion Oren
     * @version 1.0
     */
    abstract class Main extends Object {
        /** ARRAY Mapping of semester abbreviations to long names. */
        public static $SEMESTER_NAMES = array("SP"=>"Spring", "SU"=>"Summer", "FA"=>"Fall");
		/** ARRAY Mapping from campus names to their bit value (for bitmasking campuses). */
		public static $CAMPUS_MASK = 0;

        /** STRING The name of the campus we're getting courses for. */
        protected static $campus;
        /** STRING The unique name of the current semester. */
        protected static $semester;
		/** BOOLEAN True if student information should be evaluated and displayed. */
		protected static $student;
		/** BOOLEAN True if schedules should be generated. */
        protected static $submit = false;
		/** BOOLEAN True if we're dealing with traditional courses. */
        protected static $traditional;

		/** INTEGER Mask of valid campuses to display. */
		protected $campusMask = 0;

        /**
         * Initializes all the class variables.
         */
        public function __construct() {
        }

        /**
         * Returns the campus classes are coming from.
         *
         * @return STRING Campus name.
         * @see $campus
         */
        public static function getCampus() {
            return Main::$campus;
        }

        /**
         * Returns the name of the cookie that should store previous schedule information.
         *
         * @return STRING Cookie name.
         */
        public static function getCookieName() {
			$trad = (Main::isTraditional())?"trad":"non";
            return Main::getSemester().$trad.Main::getCampus();
        }

        /**
         * Returns the name of the current semester.
         *
         * @return STRING Semester identifier.
         */
        protected static function getCurrentSemester() {
            if(empty($_REQUEST["semester"])) {
                $files = getFileArray();
                return $files[0];
            } else {
                return $_REQUEST["semester"];
            }
        }

        /**
         * Returns the name of the current semester.
         *
         * @return STRING Semester identifier.
         */
        public static function getSemester() {
            return Main::$semester;
        }

        /**
         * Returns true if the user has selected at least one class.
         *
         * @return BOOLEAN True on user selection.
         */
        public static function haveSelections() {
            return isset($_REQUEST["choice"]);
        }

		/**
		 * Sets up static environment variables.
		 *
		 * @return VOID
		 */
		public static function init() {
			Main::$semester = Main::getCurrentSemester();
            Main::$traditional = !isset($_REQUEST["type"]) || $_REQUEST["type"] != "non";
			Main::$student = !isset($_REQUEST["role"]) || $_REQUEST["role"] == "student";
            Main::$campus = (isset($_REQUEST["campus"]))?$_REQUEST["campus"]:"MAIN";
            Main::$submit = isset($_REQUEST["submit"]);

			Student::init();
		}

		/**
		 * Returns true if the schedules to be displayed are for students.
		 *
		 * @return BOOLEAN True for student schedules.
		 */
		public static function isStudent() {
			return Main::$student;
		}

        /**
         * Returns true if traditional classes are being evaluated.
         *
         * @return BOOLEAN True if traditional classes are being used.
         */
        public static function isTraditional() {
            return Main::$traditional;
        }

        /**
         * Prints options for a SELECT block with all available semesters.
         *
         * @return VOID
         */
        public static function printSemesterOptions() {
            foreach(getFileArray() as $key) {
                print '<option value="'.$key.'"';
                if(Main::getSemester() == $key) {
                    print " selected='selected'";
                }
                print '>'.Main::$SEMESTER_NAMES[substr($key, -2)].' '.substr($key, 0, 4).'</option>';
            }
        }

		/**
		 * Sets the bitmask for campuses that should be displayed.
		 *
		 * @return VOID
		 */
		public function setCampusMask() {
			$this->campusMask = Main::$CAMPUS_MASK[Main::$campus];
			if(Main::$campus == "MAIN" && isset(Main::$CAMPUS_MASK["ARPT"])) {
				$this->campusMask |= Main::$CAMPUS_MASK["ARPT"];
			}
			if(isset(Main::$CAMPUS_MASK["XOL"])) {
				$this->campusMask |= Main::$CAMPUS_MASK["XOL"];
			}
			if(isset(Main::$CAMPUS_MASK["N/A"])) {
				$this->campusMask |= Main::$CAMPUS_MASK["N/A"];
			}
		}

        /**
         * Returns the full name (including path) for this script.
         *
         * @return STRING Fully-qualified PHP file path.
         */
        public function __toString() {
            return $_SERVER["PHP_SELF"];
        }
    }

	require_once($path."Student.php");
	require_once($path."Professor.php");
?>