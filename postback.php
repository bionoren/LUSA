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

    if($mode == "updateClasses") {
        $main->displaySchedules();
    }

    if($mode == "getDepartmentData") {
        print $main->getDepartmentJSON();
    }

    if($mode == "getCourseData") {
        print $main->getCourseJSON($_REQUEST["dept"]);
    }
?>