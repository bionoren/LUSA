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
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>LUSA SE</title>
<link rel="stylesheet" type="text/css" href="screen.css" media="screen,projection">
<?php
	$NUM_CLASSES = 15;
    //the limit should be in apache at ~4000 characters
    $method = (strlen($_SERVER["QUERY_STRING"]) < 3500) ? "get" : "post";

	require_once("functions.php");

    if(!isset($_REQUEST["semester"])) {
        $now = getCurrentSemester();
        $semester = "FA";
    } else {
        $semester = $_REQUEST["semester"];
        $now = getCurrentSemester("2009", $semester);
    }
	$classInfo = getClassData("2009", $semester);

	$classGroups = array();
    $classes = array();
	$courseTitleNumbers = array();
    //in all honesty, I don't remember what most of this does, just that things break if I mess with it...
    //generate select option values for display later
	foreach($classInfo as $class) {
		$course = substr($class->getCourseID(), 0, 4);
		$classGroups[$course] = '<option value="'.$course.'">'.$course.'</option>';
        $classes[$course][$class->getCourseID()] = $class->getTitle();
		$courseTitleNumbers[$class->getCourseID()][] = $class;
	}
    //alphabetize the class list
    foreach($classes as &$array) {
        asort($array);
    }
	$classGroups = implode("", $classGroups);

	if(isset($_REQUEST["submit"])) {
		//gather input data
		$courses = array();
		$errors = array();
		for($i = 0; $i < $NUM_CLASSES; $i++) {
			if($_REQUEST["class".$i] != "----" && !empty($_GET["choice".$i])) {
				$key = $_REQUEST["choice".$i];
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
		<?php print $now; ?>
		<br>
        <form method="<?php print $method; ?>">
			<select name="semester">
                <option value="SU" <?php if($_REQUEST["semester"] == "SU") { print "selected"; } ?>>Summer</option>
                <option value="FA" <?php if($_REQUEST["semester"] != "SU") { print "selected"; } ?>>Fall</option>
            </select>
            <input type="submit" name="update" value="Change">
        </form>
        <br>

        <form method="<?php print $method; ?>" id="form" name="form">
            <input type="hidden" name="semester" value="<?php print $semester; ?>">
            <?php
                if(isset($_REQUEST["submit"]) && empty($errors)) {
                    if(count($courses) > 0) {
                        if(isset($_REQUEST["sf"])) {
                            $filter = array_fill_keys($_REQUEST["sf"], true);
                        } else {
                            $filter = null;
                        }
                        if(isset($_REQUEST["cf"])) {
                            $classFilter = array_fill_keys($_REQUEST["cf"], true);
                        } else {
                            $classFilter = null;
                        }
                        $schedules = findSchedules($courses, $filter, $classFilter);

                        if(isset($_REQUEST["total"])) {
                            $total = $_REQUEST["total"];
                        } else {
                            print '<input type="hidden" name="total" value="'.count($schedules).'">';
                            $total = 0;
                        }

                        //be careful with this if statement. The order is important, because schedules[0] may or may not be an object
                        if(is_array($schedules) && (count($schedules) <= 1 || count($schedules[0]->getClasses()) != count(Schedule::$common))) {
                            print Schedule::displayCommon()."<br>";
                        }
                        if(count($schedules) > 1) {
                            displaySchedules($schedules, $total);
                        }
                    }
                }
            ?>
            <table>
                <tr>
                    <td>
                        <?php
                            $hours = 0;
                            for($i=0; $i < $NUM_CLASSES; $i++) {
                                if(isset($_REQUEST["class".$i])) {
                                    $tmp = str_replace(">".$_REQUEST["class".$i], ' selected="yes">'.$_GET["class".$i], $classGroups);
                                } else {
                                    $tmp = $classGroups;
                                }
                                print '<select name="class'.$i.'" onchange="selectChange(this, choice'.$i.');"><option value="----">----</option>'.$tmp.'</select>-';
                                print '<select id="choice'.$i.'" name="choice'.$i.'">';
                                if(!empty($_REQUEST["choice".$i])) {
                                    foreach($classes[$_REQUEST["class".$i]] as $key=>$value) {
                                        if(substr($key, strlen($key)-3) == "lab")
                                            continue;
                                        print '<option value="'.$key.'"';
                                        if($_REQUEST["choice".$i] == $key) {
                                            print ' selected="yes"';
                                            $hours += substr($key, 8);
                                        }
                                        print '>'.$value.'</option>';
                                    }
                                }
                                print '</select>';
                                if($errors[$i]):
                                ?>
                                    <font color="red">Sorry, this class is not offered this semester</font>
                                <?php endif;?>
                                <br>
                                <?php
                            }
                            print "$hours credit hours<br>";
                        ?>
                    </td>
                    <td>
                        <a href="http://www.letu.edu/academics/catalog/" target="_new"><img src="splash2.jpg"></a>
                        <br>
                            <i>Student Edition</i>
                    </td>
                </tr>
            </table>
			<?php if(isset($_REQUEST["submit"])): ?>
                <input type="submit" name="submit" value="Filter">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <?php
                    $clear = "index.php?semester=".$_REQUEST["semester"]."&total=".$_REQUEST["total"];
                    for($i = 0; $i < $NUM_CLASSES; $i++) {
                        $clear .= "&class$i=".$_REQUEST["class".$i]."&choice$i=".$_REQUEST["choice".$i];
                    }
                    $clear .= "&submit=Generate+Schedules!";
                ?>
                <a href="<?php print $clear; ?>">Clear Filters</a>
                <br>
            <?php endif; ?>
            <input type="submit" name="submit" value="Generate Schedules!">
            <a href="index.php">Clear Classes</a>
        </form>
	</div>
	<div id="footer">
		<ul>
			<li>By using this, you agree not to sue (<a href="tos.php" target="_new">blah blah blah</a>)...</li>
			<li>To register for classes log into my.letu.edu and select "Web Services - Student"</li>
			<li>Please remember that LUSA does <strong>not</strong> register you for classes</li>
		</ul>
	</div>
</div>
</body>
</html>