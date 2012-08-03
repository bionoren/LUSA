<?php
    $path = "./";
    require_once($path."functions.php");

    /**
	 * Unit tests for the getFileArray function.
	 *
	 * @return VOID
	 */
	function testGetFileArray() {
		//January 1st, 2011
		$today = mktime(0, 0, 0, 1, 1, 2011);
		$files = getFileArray(false, $today);
		assert('count($files) == 6 /* Found '.count($files).' for '.date("F jS, Y", $today).'*/');
		assert('$files[0] == "2011FA" /* Found '.$files[0].' for '.date("F jS, Y", $today).'*/');
		assert('$files[1] == "2011SU" /* Found '.$files[1].' for '.date("F jS, Y", $today).'*/'); //two into the future
		assert('$files[2] == "2011SP" /* Found '.$files[2].' for '.date("F jS, Y", $today).'*/'); //current semester
		assert('$files[3] == "2010FA" /* Found '.$files[3].' for '.date("F jS, Y", $today).'*/');
		assert('$files[4] == "2010SU" /* Found '.$files[4].' for '.date("F jS, Y", $today).'*/');
		assert('$files[5] == "2010SP" /* Found '.$files[5].' for '.date("F jS, Y", $today).'*/'); //year into the past

		//April 30th, 2011
		$today = mktime(0, 0, 0, 4, 30, 2011);
		$files = getFileArray(false, $today);
		assert('count($files) == 6 /* Found '.count($files).' for '.date("F jS, Y", $today).'*/');
		assert('$files[0] == "2011FA" /* Found '.$files[0].' for '.date("F jS, Y", $today).'*/');
		assert('$files[1] == "2011SU" /* Found '.$files[1].' for '.date("F jS, Y", $today).'*/'); //two into the future
		assert('$files[2] == "2011SP" /* Found '.$files[2].' for '.date("F jS, Y", $today).'*/'); //current semester
		assert('$files[3] == "2010FA" /* Found '.$files[3].' for '.date("F jS, Y", $today).'*/');
		assert('$files[4] == "2010SU" /* Found '.$files[4].' for '.date("F jS, Y", $today).'*/');
		assert('$files[5] == "2010SP" /* Found '.$files[5].' for '.date("F jS, Y", $today).'*/'); //year into the past

		//May 1st, 2011
		$today = mktime(0, 0, 0, 5, 1, 2011);
		$files = getFileArray(false, $today);
		assert('count($files) == 6 /* Found '.count($files).' for '.date("F jS, Y", $today).'*/');
		assert('$files[0] == "2012SP" /* Found '.$files[0].' for '.date("F jS, Y", $today).'*/');
		assert('$files[1] == "2011FA" /* Found '.$files[1].' for '.date("F jS, Y", $today).'*/'); //two into the future
		assert('$files[2] == "2011SU" /* Found '.$files[2].' for '.date("F jS, Y", $today).'*/'); //current semester
		assert('$files[3] == "2011SP" /* Found '.$files[3].' for '.date("F jS, Y", $today).'*/');
		assert('$files[4] == "2010FA" /* Found '.$files[4].' for '.date("F jS, Y", $today).'*/');
		assert('$files[5] == "2010SU" /* Found '.$files[5].' for '.date("F jS, Y", $today).'*/'); //year into the past

		//July 31st, 2011
		$today = mktime(0, 0, 0, 7, 31, 2011);
		$files = getFileArray(false, $today);
		assert('count($files) == 6 /* Found '.count($files).' for '.date("F jS, Y", $today).'*/');
		assert('$files[0] == "2012SP" /* Found '.$files[0].' for '.date("F jS, Y", $today).'*/');
		assert('$files[1] == "2011FA" /* Found '.$files[1].' for '.date("F jS, Y", $today).'*/'); //two into the future
		assert('$files[2] == "2011SU" /* Found '.$files[2].' for '.date("F jS, Y", $today).'*/'); //current semester
		assert('$files[3] == "2011SP" /* Found '.$files[3].' for '.date("F jS, Y", $today).'*/');
		assert('$files[4] == "2010FA" /* Found '.$files[4].' for '.date("F jS, Y", $today).'*/');
		assert('$files[5] == "2010SU" /* Found '.$files[5].' for '.date("F jS, Y", $today).'*/'); //year into the past

		//August 1st, 2011
		$today = mktime(0, 0, 0, 8, 1, 2011);
		$files = getFileArray(false, $today);
		assert('count($files) == 6 /* Found '.count($files).' for '.date("F jS, Y", $today).'*/');
		assert('$files[0] == "2012SU" /* Found '.$files[0].' for '.date("F jS, Y", $today).'*/');
		assert('$files[1] == "2012SP" /* Found '.$files[1].' for '.date("F jS, Y", $today).'*/'); //two into the future
		assert('$files[2] == "2011FA" /* Found '.$files[2].' for '.date("F jS, Y", $today).'*/'); //current semester
		assert('$files[3] == "2011SU" /* Found '.$files[3].' for '.date("F jS, Y", $today).'*/');
		assert('$files[4] == "2011SP" /* Found '.$files[4].' for '.date("F jS, Y", $today).'*/');
		assert('$files[5] == "2010FA" /* Found '.$files[5].' for '.date("F jS, Y", $today).'*/'); //year into the past

		//December 31st, 2011
		$today = mktime(0, 0, 0, 12, 31, 2011);
		$files = getFileArray(false, $today);
		assert('count($files) == 6 /* Found '.count($files).' for '.date("F jS, Y", $today).'*/');
		assert('$files[0] == "2012SU" /* Found '.$files[0].' for '.date("F jS, Y", $today).'*/');
		assert('$files[1] == "2012SP" /* Found '.$files[0].' for '.date("F jS, Y", $today).'*/'); //two into the future
		assert('$files[2] == "2011FA" /* Found '.$files[0].' for '.date("F jS, Y", $today).'*/'); //current semester
		assert('$files[3] == "2011SU" /* Found '.$files[0].' for '.date("F jS, Y", $today).'*/');
		assert('$files[4] == "2011SP" /* Found '.$files[0].' for '.date("F jS, Y", $today).'*/');
		assert('$files[5] == "2010FA" /* Found '.$files[0].' for '.date("F jS, Y", $today).'*/'); //year into the past
	}

    function testFindSchedules() {
        $classes = array();
    }

    testGetFileArray();
?>