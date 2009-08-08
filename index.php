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
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
<title>LUSA SE</title>
<link rel="stylesheet" type="text/css" href="screen.css" media="screen,projection">
<link rel="stylesheet" type="text/css" href="print.css" media="print">
<?php
	$NUM_CLASSES = 15;
    //the limit should be in apache at ~4000 characters
    $method = (strlen($_SERVER["QUERY_STRING"]) < 3500) ? "get" : "post";

    if($_REQUEST["submit"] != "Filter") {
        unset($_REQUEST["sf"]);
        unset($_REQUEST["cf"]);
        unset($_REQUEST["total"]);
    }

    if(!isset($_REQUEST["semester"])) {
        $files = getFileArray();
        if(is_array($files[1])) {
            //never default to the summer unless there's no fall data yet
            if($files[1][1] == "SU" && is_array($files[2])) {
                $semester = $files[2];
            } else {
                $semester = $files[1];
            }
        } else {
            $semester = $files[0];
        }
    } else {
        $semester = explode(" ", $_REQUEST["semester"]);
    }
    $now = getCurrentSemester($semester[0], $semester[1]);

	$classGroups = array();
    $classes = array();
	$courseTitleNumbers = array();
    //in all honesty, I don't remember what most of this does, just that things break if I mess with it...
    if(isset($_REQUEST["cf"])) {
        $classFilter = array_fill_keys($_REQUEST["cf"], true);
    } else {
        $classFilter = null;
    }

    //generate select option values for display later
	foreach(getClassData($semester[0], $semester[1]) as $class) {
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
                $errors[$i] = true;
            }
		}
	}
    //find possible schedules

    //I really don't know javascript, so make whatever sense of this you can...
?>
        <script type="text/javascript">
            <!--
            <?php
                print 'var arrItems = new Array();';
                print "\n";
                $i = 0;
                foreach($classes as $group=>$class) {
                    print 'var arrItems'.$i.' = Array();';
                    print 'arrItems["'.$group.'"] = "'.$i++.'";';
                    print "\n";
                }
                $i = 0;
                foreach($classes as $group=>$class) {
                    foreach($class as $id=>$title) {
                        if(substr($id, strlen($id)-3) == "lab")
                            continue;
                        print 'arrItems'.$i.'["'.$id.'"] = "'.$title.'";';
                        print "\n";
                    }
                    $i++;
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
                myEle.setAttribute("value","0");
                controlToPopulate.appendChild(myEle);
                var group = arrItems[control.value];
                var foo = eval('arrItems'+group);
                for(x in foo) {
                    myEle = document.createElement("option");
                    myEle.setAttribute("value",x);
                    var txt = document.createTextNode(foo[x]);
                    myEle.appendChild(txt);
                    controlToPopulate.appendChild(myEle);
                }
            }
            // -->
        </script>
</head>
<body>
<div id="container">
	<div id="header">
		<h1>LUSA</h1>
	</div>
	<div id="body">
		<div id="semester">
		<?php print $now; ?>
		<br>
        <form method="<?php print $method; ?>" action="./">
			<select name="semester">
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
            <input type="submit" name="update" value="Change">
        </form>
        </div>
        <br>

        <form method="<?php print $method; ?>" id="form" name="form" action="./">
            <input type="hidden" name="semester" value="<?php print $semesterStr; ?>">
            <?php
                if(isset($_REQUEST["submit"]) && empty($errors)) {
                    if(count($courses) > 0) {
                        if(isset($_REQUEST["sf"])) {
                            $filter = array_fill_keys($_REQUEST["sf"], true);
                        } else {
                            $filter = null;
                        }

                        $schedules = findSchedules($courses, $filter);

                        if(isset($_REQUEST["total"])) {
                            $total = $_REQUEST["total"];
                            print '<input type="hidden" name="total" value="'.$total.'">';
                        } else {
                            print '<input type="hidden" name="total" value="'.count($schedules).'">';
                            $total = count($schedules);
                        }

                        if(is_array($schedules)) {
                            Schedule::displayCommon($total)."<br>";
                            if(count($schedules) > 1) {
                                displaySchedules($schedules, $total);
                            }
                        } else {
                            //believe it or not, this is error handling
                            displaySchedules($schedules, $total);
                        }
                    }
                }
            ?>
            <div class="leftcol print-no">
                        <?php
                            $hours = 0;
                            $classGroups = implode("", $classGroups);
                            for($i=0; $i < $NUM_CLASSES; $i++) {
                                if(isset($_REQUEST["class"][$i])) {
                                    $tmp = str_replace(">".$_REQUEST["class"][$i], ' selected="selected">'.$_REQUEST["class"][$i], $classGroups);
                                } else {
                                    $tmp = $classGroups;
                                }
                                ?>
                                <select name="class[]" onchange="selectChange(this, choice<?php echo $i?>);"><option value="0">----</option><?php echo $tmp?></select>
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
                                else {
                                	?>
                                	<option value="" disabled="disabled"></option>
                                	<?php
                                }
                                ?>
                                </select>
                                <?php
                                    if($populated !== false) {
                                        print '&nbsp;&nbsp;'.Course::displayBookStoreLink($populated);
                                    }
                                    if($errors[$i]):
                                ?>
                                    <font color="red">Sorry, this class is not offered this semester</font>
                                <?php endif;?>
                                <br>
                                <?php
                            } ?>
                            <?php echo $hours?> credit hours<br>
                    </div>
                    <div class="rightcol print-no" style="text-align:right;">
                        <a href="http://www.letu.edu/academics/catalog/" target="_new"><img src="splash2.jpg" alt="LUSA"></a>
                        <br>
                            <em>Student Edition</em>
                    </div>
                    <div class="clear"></div>
                    
			<?php if(isset($_REQUEST["submit"])): ?>
			<div class="print-no">
                <input type="submit" name="submit" value="Filter">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <?php
                    $clear = "./?semester=".$_REQUEST["semester"];
                    for($i = 0; $i < $NUM_CLASSES; $i++) {
                        if(!empty($_REQUEST["choice"][$i])) {
                            $clear .= "&amp;class[]=".$_REQUEST["class"][$i]."&amp;choice[]=".$_REQUEST["choice"][$i];
                        } else {
                            $clear .= "&amp;class[]=0";
                        }
                    }
                    $clear .= "&amp;submit=Filter";
                ?>
                <a href="<?php print $clear; ?>">Clear Filters</a>
                <br>
                </div>
            <?php endif; ?>
            <div class="print-no">
            <br>
            <input type="submit" name="submit" value="Generate Schedules!">
            <a href="index.php?ignore=true">Clear Classes</a>
            </div>
        </form>
	</div>
	<div id="footer">
		<ul>
            <li><b>To register for classes log into <a href="https://my.letu.edu:91/cgi-bin/student/frame.cgi" target="_blank">my.letu.edu</a> and select "Web Services - Student"</b></li>
			<li><b>Please remember that LUSA does <strong style="color:red;">not</strong> register you for classes</b></li>
			<li>By using this, you agree not to sue (<a href="tos.php" target="_new">blah blah blah</a>)...</li>
		</ul>
	</div>
</div>
</body>
</html>