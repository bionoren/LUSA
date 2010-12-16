<?php
    //Shutter would like to note:
    //We wish you a Merry Christmas and a happy New Year!
    $path = "./";
    require_once($path."Main.php");
    $mode = $_REQUEST["mode"];
    if(isset($_REQUEST["data"])) {
        $data = $_REQUEST["data"];
        parse_str($data, $tmp);
        $_REQUEST = array_merge($tmp, $_REQUEST);
        unset($_REQUEST["data"]);
    }

    Main::init();
    if(Main::isStudent()) {
        $main = new Student();
    } else {
        $main = new Professor();
    }

    if($mode == "updateSchedule") {
        $main->displaySchedules();
    }

    if($mode == "addClass") {
        $courses = Course::getFromID($_REQUEST["id"]);
        $optional = count($courses) > 1;
        if($optional) {
            $key = current($courses)->getID();
            $span = (Main::isTraditional())?7:9;
            print '<tr style="cursor:pointer;" class="'.$key.'" onclick="Course.toggle(\''.$key.'\');"><td><span id="'.$key.'">+</span> '.$key.'</td><td colspan="'.($span-1).'">'.current($courses)->getTitle().' ('.count($courses).')</td></tr>';
        }
        foreach($courses as $course) {
            $course->display($optional);
        }
    }

    if($mode == "getDepartmentData") {
        print $main->getDepartmentJSON($_REQUEST["dept"]);
    }

    if($mode == "getCourseData") {
        print $main->getCourseJSON($_REQUEST["dept"], $_REQUEST["course"]);
    }

    if($mode == "updateAll") {
        $main->display();
    }
?>