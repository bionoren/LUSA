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
	$main = new Main();

    if(isset($_REQUEST["submit"])) {
        save_cookie($_SERVER["QUERY_STRING"]);
    } else {
        //look for cookie data
        if(isset($_COOKIE[Main::getSemester()]) && !isset($_REQUEST["ignore"])) {
            header("Location:".$_SERVER["PHP_SELF"]."?".$_COOKIE[Main::getSemester()]);
        }
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
        <script type="text/javascript" src="layout/functions.js"></script>
        <script type="text/javascript">
            <!--
            <?php $main->printHeaderJS(); ?>
            // -->
        </script>
    </head>
    <body lang="en">
        <!--LUSA 2: A Dorm 41 Production-->
        <!--Developed by: Wharf-->
        <!--Design by: Shutter-->
        <!--JavaScript Magic: Fjord-->
        <!--QA and Lead Tester: Synk-->
        <!--This code hates Tom Kelley-->
        <!--Special thanks to 41 and G2 for their suggestions, bug reports, and encouragement!-->
        <div id="container">
            <form method="get" id="form" action="<?php print $main; ?>">
                <div id="header">
                    <h1>LUSA</h1>
                    <ul id="options">
                        <li class="first">
                            <div style="display:inline">
                                <input type="radio" id="typeTraditional" name="type" value="trad" <?php if(Main::isTraditional()) { print 'checked="checked"'; } ?>/>
                                <label for="typeTraditional">Traditional</label>
                                &nbsp;&nbsp;
                                <input type="radio" id="typeNonTraditional" name="type" value="non" <?php if(!Main::isTraditional()) { print 'checked="checked"'; } ?>/>
                                <label for="typeNonTraditional">Non-Traditional</label>
                            </div>
                        </li>
                        <?php if(!Main::isTraditional()) { ?>
                            <li>
                                <div style="display:inline">
                                    <select name="campus" id="campusSelect">
                                        <option value="AUS" <?php if(Main::getCampus() == "AUS") print "selected='selected'"; ?>>Austin</option>
                                        <option value="BED" <?php if(Main::getCampus() == "BED") print "selected='selected'"; ?>>Bedford</option>
                                        <option value="DAL" <?php if(Main::getCampus() == "DAL") print "selected='selected'"; ?>>Dallas</option>
                                        <option value="HOU" <?php if(Main::getCampus() == "HOU") print "selected='selected'"; ?>>Houston</option>
                                        <option value="MAIN" <?php if(Main::getCampus() == "MAIN") print "selected='selected'"; ?>>Longview</option>
                                        <option value="TYL" <?php if(Main::getCampus() == "TYL") print "selected='selected'"; ?>>Tyler</option>
                                        <option value="WES" <?php if(Main::getCampus() == "WES") print "selected='selected'"; ?>>Westchase</option>
                                        <option value="ONL" <?php if(Main::getCampus() == "ONL") print "selected='selected'"; ?>>Online</option>
                                    </select>
                                    <script type="text/javascript">
                                        <!--
                                        $('campusSelect').observe('change', selectCampusTrigger);
                                        //-->
                                    </script>
                                </div>
                            </li>
                        <?php } ?>
                        <li>
                            <div style="display:inline">
                                <select name="semester" id="semesterSelect">
                                    <?php Main::printSemesterOptions(); ?>
                                </select>
								<label for="semesterSelect" style="display:none;">Select Semester</label>
                            </div>
                        </li>
                        <li>
                            <input type="checkbox" name="showBooks" id="showBooks" <?php if($main->showBooks()) print "checked"; ?>/>
                            <label for="showBooks">Bookstore Links</label>
                        </li>
                    </ul>
                    <script type="text/javascript">
                        <!--
                        var path = window.location.protocol + '//' + window.location.host + window.location.pathname;
                        var endPath = '&semester=' + escape($('semesterSelect').value) + '&ignore=true';
                        $('typeTraditional').observe('click', function(event) {
                            window.location = path + '?type=trad' + endPath;
                        });
                        $('typeNonTraditional').observe('click', function(event) {
                            window.location = path + '?type=non' + endPath;
                        });
                        $('semesterSelect').observe('change', function(event) {
                            window.location = path + '?type=' + ($('typeTraditional').checked == true ? 'trad' : 'non') + '&semester=' + escape(this.value);
                        });
                        //-->
                    </script>
                </div>
                <div id="body">
                    <?php $main->displaySchedules(); ?>
                    <div class="print-no">
                        <h2>Selected Classes</h2>
                        <?php $main->printClassDropdowns(); ?>
                        <?php print $main->getHours() ?> Credit Hours
                        <br/><br/>
                        <a href="index.php?ignore=true" class="button">Clear Classes</a>
                        <?php
							$clear = $main->getClearFilterLink();
							if($clear) {
								print '&nbsp;&nbsp;<a href="'.$clear.'" class="button">Clear Filters</a>';
							}
                        ?>
                        <br/><br/>
                        <input type="submit" name="submit" value="Update Schedule"/>
                    </div>
                </div>
            </form>
            <div id="footer">
                <ul>
                    <li>Remember that LUSA <span style="color:red;">does not</span> register you for classes. You can <a href="https://my.letu.edu:91/cgi-bin/student/frame.cgi">log into MyLetu to register for classes</a>.</li>
                    <li>By using this, you agree not to sue (<a href="tos.php">blah blah blah</a>).</li>
                </ul>
            </div>
        </div>
    </body>
</html>