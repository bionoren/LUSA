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

	error_reporting(E_ALL);
	date_default_timezone_set("America/Chicago");

	require_once("Main.php");
	Main::init();

    if(Main::isSubmitted() && Main::isStudent()) {
        save_cookie($_SERVER["QUERY_STRING"]);
    } else {
        //look for cookie data
        if(Main::isStudent() && isset($_COOKIE[Main::getCookieName()]) && !isset($_REQUEST["ignore"])) {
			$_SERVER["QUERY_STRING"] = $_COOKIE[Main::getCookieName()];
			$tmp = array();
			parse_str($_SERVER["QUERY_STRING"], $tmp);
			$_REQUEST = array_merge($tmp, $_REQUEST);
			Main::init();
        }
    }

	if(Main::isStudent()) {
		$main = new Student();
	} else {
		$main = new Professor();
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
        <meta http-equiv="content-language" content="en"/>
        <meta http-equiv="Content-Style-Type" content="text/css"/>
        <meta http-equiv="Content-Script-Type" content="text/javascript"/>
        <meta name="language" content="en"/>
        <meta name="description" content="Helps LETU students figure out their class schedules"/>
        <meta name="keywords" content="LETU LeTourneau student schedule class classes"/>

        <title>LUSA SE</title>
        <link rel="stylesheet" type="text/css" href="layout/screen.css" media="screen,projection"/>
        <link rel="stylesheet" type="text/css" href="layout/print.css" media="print"/>
        <script type="text/javascript" src="layout/prototype.js"></script>
        <script type="text/javascript" src="layout/functions-orig.js"></script>
    </head>
    <body lang="en" onload="var lusa = new LUSA();">
        <!--LUSA 2: A Dorm 41 Production-->
        <!--Developed by: Wharf-->
        <!--Design by: Shutter-->
        <!--QA and Lead Tester: Synk-->
        <!--Performance Consultants: Zoot, Gary Raduns-->
        <!--This code hates Tom Kelley-->
        <!--Special thanks to all of 41 and G2 for their suggestions, bug reports, patience, and encouragement!-->
        <div id="container">
            <form method="get" id="form" action="<?php print $main; ?>">
                <div id="header">
                    <h1><a href="<?php print $main; ?>" style="text-decoration:inherit; color:inherit;">LUSA</a></h1>
					<ul id="options">
						<li class="first">
							<input type="radio" id="typeStudent" name="role" value="student" <?php if(Main::isStudent()) { print 'checked="checked"'; } ?> onclick="lusa.setStudent(true);"/>
							<label for="typeStudent">Student</label>
							<!--&nbsp;&nbsp;
							<input type="radio" id="typeProf" name="role" value="prof" <?php if(!Main::isStudent()) { print 'checked="checked"'; } ?> onclick="lusa.setStudent(false);"/>
							<label for="typeProf">Professor</label>-->
						</li>
                        <li class="second">
                            <div style="display:inline">
                                <input type="radio" id="typeTraditional" name="type" value="trad" <?php if(Main::isTraditional()) { print 'checked="checked"'; } ?> onclick="lusa.setTrad(true);"/>
                                <label for="typeTraditional">Traditional</label>
                                &nbsp;&nbsp;
                                <input type="radio" id="typeNonTraditional" name="type" value="non" <?php if(!Main::isTraditional()) { print 'checked="checked"'; } ?> onclick="lusa.setTrad(false);"/>
                                <label for="typeNonTraditional">Non-Traditional</label>
                            </div>
                        </li>
                        <?php if(!Main::isTraditional()) { ?>
                            <li>
                                <div style="display:inline">
                                    <select name="campus" id="campusSelect" onclick="lusa.setCampus(this.value)">
<!--                                        <option value="AUS" <?php if(Main::getCampus() == "AUS") print "selected='selected'"; ?>>Austin</option>-->
                                        <option value="BED" <?php if(Main::getCampus() == "BED") print "selected='selected'"; ?>>Bedford</option>
                                        <option value="DAL" <?php if(Main::getCampus() == "DAL") print "selected='selected'"; ?>>Dallas</option>
                                        <option value="HOU" <?php if(Main::getCampus() == "HOU") print "selected='selected'"; ?>>Houston</option>
                                        <option value="MAIN" <?php if(Main::getCampus() == "MAIN") print "selected='selected'"; ?>>Longview</option>
                                        <option value="TYL" <?php if(Main::getCampus() == "TYL") print "selected='selected'"; ?>>Tyler</option>
                                        <option value="WES" <?php if(Main::getCampus() == "WES") print "selected='selected'"; ?>>Westchase</option>
                                        <option value="XOL" <?php if(Main::getCampus() == "XOL") print "selected='selected'"; ?>>Online</option>
                                    </select>
									<label for="campusSelect" style="display:none">Select Campus</label>
                                </div>
                            </li>
                        <?php } ?>
                        <li>
                            <div style="display:inline">
                                <select name="semester" id="semesterSelect" onclick="lusa.setSemester(this.value)">
                                    <?php Main::printSemesterOptions(); ?>
                                </select>
								<label for="semesterSelect" style="display:none;">Select Semester</label>
                            </div>
                        </li>
                    </ul>
                </div>
                <div id="body">
                    <?php $main->display(); ?>
                </div>
            </form>
            <div id="footer">
                <ul>
					<li class="print-no"><a href="#" onclick='window.open("http://www.letu.edu/academics/catalog/");'>Course Catalog</a></li>
                    <li>Remember that LUSA <span style="color:red;">does not</span> register you for classes. You can <a href="https://my.letu.edu:91/cgi-bin/student/frame.cgi">log into MyLetu to register for classes</a>.</li>
                    <li class="print-no">By using this, you agree not to sue (<a href="tos.php">blah blah blah</a>).</li>
                </ul>
            </div>
        </div>
    </body>
</html>