<?php
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
        print '{"classes":[';
        $optional = count($courses) > 1;
        for($i = 0; $i < count($courses);) {
            ob_start();
            $courses[$i++]->display($optional);
            $data = ob_get_contents();
            ob_end_clean();
            print json_encode($data);
            if($i < count($courses)) {
                print ',';
            }
        }
        print ']}';
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