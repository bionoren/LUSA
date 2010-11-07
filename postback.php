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

    if($mode == "createClassDropdown") {
        $main->printClassDropdown($_REQUEST["department"], $_REQUEST["selection"]);
    }

    if($mode == "updateAll") {
        $main->display();
    }
?>