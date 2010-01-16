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

    //whatever happens, cookie stuff comes first
    if(isset($_REQUEST["submit"])) {
        save_cookie($_SERVER["QUERY_STRING"]);
    } else {
        //look for cookie data
        if(isset($_COOKIE["lastSchedule"]) && !isset($_REQUEST["ignore"])) {
            header("Location:./?".$_COOKIE["lastSchedule"]);
        }
    }

    //for those of you wondering why this number is so high, I know an aviation major taking 11 classes next semester.
	$NUM_CLASSES = 20;
    //the limit is in apache at ~4000 characters by my analysis
    $method = (strlen($_SERVER["QUERY_STRING"]) < 3500) ? "get" : "post";

    if(!isset($_REQUEST["semester"])) {
        $files = getFileArray();
        $semester = $files[0];
    } else {
        $semester = explode(" ", $_REQUEST["semester"]);
    }
    $now = getCurrentSemester($semester[0], $semester[1], $_REQUEST["type"] != "non");

    //if the class hash changed, we need to unset the schedule filter array (sf[])
    //we keep the class filters, since if you didn't want it before, ... why would you want it now?
    if(array_key_exists("sf", $_REQUEST) && crc32(implode($_REQUEST["choice"])) != $_REQUEST["ch"]) {
        unset($_REQUEST["sf"]);
    }

	$classGroups = array();
    $classes = array();
	$courseTitleNumbers = array();
    if(isset($_REQUEST["cf"])) {
        $classFilter = array_fill_keys($_REQUEST["cf"], true);
    } else {
        $classFilter = null;
    }

    if(isset($_REQUEST["campus"])) {
        $campus = $_REQUEST["campus"];
    } else {
        $campus = "MAIN";
    }
    //generate select option values for display later
	foreach(getClassData($semester[0], $semester[1], $_REQUEST["type"] != "non", $campus) as $class) {
        if(isset($classFilter[$class->getID()])) {
            continue;
        }
		$course = substr($class->getCourseID(), 0, 4);
		$classGroups[$course] = '<option value="'.$course.'">'.$course.'</option>';
        $classes[$course][$class->getCourseID()] = $class->getTitle();
		$courseTitleNumbers[$class->getCourseID()][] = $class;
	}
    //alphabetize the class list
    foreach($classes as &$array) {
        asort($array);
    }

	if(isset($_REQUEST["submit"]) && isset($_REQUEST["choice"])) {
        $_REQUEST["class"] = array_values(array_filter($_REQUEST["class"]));
		//gather input data
		$courses = array();
		$errors = array();
		foreach($_REQUEST["choice"] as $key) {
            if(isset($courseTitleNumbers[$key])) {
                $courses[] = $courseTitleNumbers[$key];
                if(isset($courseTitleNumbers[$key." lab"])) {
                    $courses[] = $courseTitleNumbers[$key." lab"];
                }
            } else {
                $errors[$key] = true;
            }
		}
	}
    //find possible schedules
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
    <title>LUSA SE</title>
    <link rel="stylesheet" type="text/css" href="screen.css" media="screen,projection">
    <link rel="stylesheet" type="text/css" href="print.css" media="print">
    <script type="text/javascript" src="prototype.js"></script>
        <script type="text/javascript">
            <!--
            <?php
                print 'var arrItems = new Hash();';
                print "\n";
                foreach($classes as $group=>$class) {
                    print 'arrItems.set("'.$group.'", new Hash());';
                    print "\n";
                    foreach($class as $id=>$title) {
                        if(substr($id, -3) == "lab")
                            continue;
                        print 'arrItems.get("'.$group.'").set("'.$id.'", "'.$title.'");';
                        print "\n";
                    }
                }
            ?>

            function selectChange(control, controlToPopulate) {
                // Empty the second drop down box of any choices
                for(var q=controlToPopulate.options.length; q>=0; q--)
                    controlToPopulate.options[q]=null;
                // ADD Default Choice - in case there are no values
                var myEle = document.createElement("option");
                theText = document.createTextNode("----");
                myEle.appendChild(theText);
                controlToPopulate.appendChild(myEle);
                if(arrItems.get(control.value) == null) {
                    //some browsers (read some versions of some browsers) feel obligated to pass
                    //on empty values if a select statement is populated with an empty option
                    //Therefore, we make empty fields truly empty here.
                    for(var q=controlToPopulate.options.length; q>=0; q--)
                        controlToPopulate.options[q]=null;
                    return;
                }
                arrItems.get(control.value).each(function(pair) {
                    myEle = document.createElement("option");
                    myEle.setAttribute("value", pair.key);
                    var txt = document.createTextNode(pair.value);
                    myEle.appendChild(txt);
                    controlToPopulate.appendChild(myEle);
                });
            }
            // -->
        </script>
    </head>
<body>
<!--LUSA 2: A Dorm 41 Production-->
<!--Developed by: Wharf-->
<!--Design by: Shutter-->
<!--JavaScript Voodoo: Fjord-->
<!--Lead Tester: Synk-->
<!--This code hates Tom Kelley-->
<!--Special thanks to all the 41ers for their suggestions, bug reports, and encouragement!-->
<form method="<?php print $method; ?>" id="form" name="form" action="./">
<div id="container">
  <div id="header">
    <h1>LUSA</h1>
    <ul id="options">
      <li class="first">
        <input type="radio" id="typeTraditional" name="type" value="trad" <?php if(isTraditional()) { print 'checked="checked"'; } ?> onClick="window.location = window.location.protocol + '//' + window.location.host + window.location.pathname + '?type=trad&amp;semester=' + escape($('semesterSelect').value) + '&amp;ignore=true'"> <label for="typeTraditional">Traditional</label>
        &nbsp;&nbsp;
        <input type="radio" id="typeNonTraditional" name="type" value="non" <?php if(!isTraditional()) { print 'checked="checked"'; } ?> onClick="window.location = window.location.protocol + '//' + window.location.host + window.location.pathname + '?type=non&amp;semester=' + escape($('semesterSelect').value) + '&amp;ignore=true'"> <label for="typeNonTraditional">Non-Traditional</label>
      </li>
      <?php if(!isTraditional()) { ?>
        <li>
          <select name="campus">
            <option value="AUS" <?php if($_REQUEST["campus"] == "AUS") print "selected"; ?>>Austin</option>
            <option value="BED" <?php if($_REQUEST["campus"] == "BED") print "selected"; ?>>Bedford</option>
            <option value="DAL" <?php if($_REQUEST["campus"] == "DAL") print "selected"; ?>>Dallas</option>
            <option value="HOU" <?php if($_REQUEST["campus"] == "HOU") print "selected"; ?>>Houston</option>
            <option value="MAIN" <?php if(!isset($_REQUEST["campus"]) || $_REQUEST["campus"] == "MAIN") print "selected"; ?>>Longview</option>
            <option value="TYL" <?php if($_REQUEST["campus"] == "TYL") print "selected"; ?>>Tyler</option>
            <option value="WES" <?php if($_REQUEST["campus"] == "WES") print "selected"; ?>>Westchase</option>
            <option value="ONL" <?php if($_REQUEST["campus"] == "ONL") print "selected"; ?>>Online</option>
          </select>
        </li>
      <?php } ?>
      <li>
        <select name="semester" id="semesterSelect" onChange="window.location = window.location.protocol + '//' + window.location.host + window.location.pathname + '?type=' + ($('typeTraditional').checked == true ? 'trad' : 'non') + '&amp;semester=' + escape(this.value) + '&amp;submit=Change'">
            <?php
                $files = getFileArray();
                $names = array("SP"=>"Spring", "SU"=>"Summer", "FA"=>"Fall");
                $semesterStr = $semester[0].' '.$semester[1];
                for($i = 0; $i < count($files); $i++) {
                    $key = $files[$i][0].' '.$files[$i][1];
                    print '<option value="'.$key.'"';
                    if($semesterStr == $key) {
                        print "selected";
                    }
                    print '>'.$names[$files[$i][1]].' '.$files[$i][0].'</option>';
                }
            ?>
        </select>
      </li>
      <li>
        <input type="checkbox" name="showBooks" id="showBooks" <?php if(isset($_REQUEST["showBooks"]) && $_REQUEST["showBooks"] == "on") print "checked"; ?>>
        <label for="showBooks">Bookstore Links</label>
      </li>
    </ul>
  </div>
  <div id="body">
                <?php
                    if(isset($_REQUEST["cf"])) {
                        foreach($_REQUEST["cf"] as $val) {
                            print '<input type="hidden" name="cf[]" value="'.$val.'">';
                        }
                    }
                    if(isset($_REQUEST["sf"])) {
                        foreach($_REQUEST["sf"] as $val) {
                            print '<input type="hidden" name="sf[]" value="'.$val.'">';
                        }
                    }
                ?>
                <input type="hidden" name="semester" value="<?php print $semesterStr; ?>">
                <?php
                    if(isset($_REQUEST["submit"]) && empty($errors)) {
                        if(count($courses) > 0) {
                            $schedules = findSchedules($courses, $_REQUEST["sf"]);

                            if(isset($_REQUEST["total"])) {
                                $total = $_REQUEST["total"];
                                print '<input type="hidden" name="total" value="'.$total.'">';
                            } else {
                                print '<input type="hidden" name="total" value="'.count($schedules).'">';
                                $total = count($schedules);
                            }

                            if(is_array($schedules)) {
                            	?><h2>Schedule</h2><?php
                                Schedule::displayCommon($total)."<br>";
                                if(count($schedules) > 1) {
                                    displaySchedules($schedules, $total);
                                }
                            } else {
                                //believe it or not, this also does error handling
                                displaySchedules($schedules, $total);
                            }
                        }
                    }
                ?><br>
                <div class="print-no">
                <h2>Selected Classes</h2>
                            <?php
                                //class hash
                                //crc32 is fast and should be good enough here to distinguish different class sets,
                                //and it keeps the url small
                                if(array_key_exists("choice", $_REQUEST)) {
                                    print '<input type="hidden" name="ch" value="'.crc32(implode($_REQUEST["choice"])).'">';
                                }
                                $hours = 0;
                                $classGroups = implode("", $classGroups);
                                $activeSelect = 0;
                                for($i=0; $i < $NUM_CLASSES; $i++) {
                                    if(isset($_REQUEST["class"][$i])) {
                                        $tmp = str_replace(">".$_REQUEST["class"][$i], ' selected="selected">'.$_REQUEST["class"][$i], $classGroups);
                                    } else {
                                        $tmp = $classGroups;
                                    }
                                    ?>
                                    <div id="classChoice<?php echo $i?>">
                                    <select name="class[]" onchange="selectChange(this, choice<?php echo $i?>);Element.show('classChoice<?php echo $i+1?>')"><option value="0">----</option><?php echo $tmp?></select>
                                    <select id="choice<?php echo $i?>" name="choice[]">
                                    <?php
                                    $populated = false;
                                    if(!empty($_REQUEST["choice"][$i])) {
                                        foreach($classes[$_REQUEST["class"][$i]] as $key=>$value) {
                                            if(substr($key, strlen($key)-3) == "lab")
                                                continue;
                                            print '<option value="'.$key.'"';
                                            if($_REQUEST["choice"][$i] == $key) {
                                                print ' selected="selected"';
                                                $hours += substr($key, 8);
                                                $populated = $key;
                                            }
                                            print '>'.$value.'</option>';
                                        }
                                    }
                                    ?>
                                    </select>
                                    <?php if(empty($_REQUEST["choice"][$i])):?>
                                    <script type="text/javascript">Element.hide('classChoice<?php echo $i?>');</script>
                                    <?php else: $activeSelect = $i+1; endif;?>
                                    <?php
                                        if($populated !== false && $_REQUEST["showBooks"] == "on") {
                                            print '&nbsp;&nbsp;'.Course::displayBookStoreLink($populated);
                                        }
                                        if($errors[$i]):
                                    ?>
                                        <font color="red">Sorry, this class is not offered this semester</font>
                                    <?php endif;?>
                                    </div>
                                    <?php
                                } // foreach ?>
                                <script type="text/javascript">Element.show('classChoice<?php echo $activeSelect?>');</script>
                                <?php echo $hours?> Credit Hours<br><br>



                                <a href="index.php?ignore=true" class="button">Clear Classes</a>

                                <?php if(isset($_REQUEST["submit"])): ?>
                    <?php
                        $clear = "./?semester=".$_REQUEST["semester"];
                        for($i = 0; $i < $NUM_CLASSES; $i++) {
                            if(!empty($_REQUEST["choice"][$i])) {
                                $clear .= "&amp;class[]=".$_REQUEST["class"][$i]."&amp;choice[]=".$_REQUEST["choice"][$i];
                            } else {
                                $clear .= "&amp;class[]=0";
                            }
                        }
                        $clear .= "&amp;type=".$_REQUEST["type"];
                        if(isset($_REQUEST["campus"])) {
                            $clear .= "&amp;campus=".$_REQUEST["campus"];
                        }
                        $clear .= "&amp;submit=Filter";
                    ?>
                    <a href="<?php print $clear; ?>" class="button">Clear Filters</a>
                <?php endif; ?>

                        </div><br>


                <div class="print-no">
                <input type="submit" name="submit" value="Update Schedule">
                </div>
        </div>
        <div id="footer">
            <ul>
                <li>Remember that LUSA <span style="color:red;">does not</span> register you for classes. You can <a href="https://my.letu.edu:91/cgi-bin/student/frame.cgi" target="_blank">log into MyLetu to register for classes</a>.</li>
                <li>By using this, you agree not to sue (<a href="tos.php" target="_new">blah blah blah</a>).</li>
            </ul>
        </div>
</div>
</form>
</body>
</html>