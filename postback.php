<?php
    //Shutter would like to note:
    //We wish you a Merry Christmas and a happy New Year!
    $path = "./";
    require_once($path."Main.php");
    require_once($path."smarty/Smarty.class.php");

    $mode = $_REQUEST["mode"];

    $smarty = new Smarty();
    $data = new Smarty_Data();
    Main::init();

    if(Main::isStudent()) {
        $main = new Student();
    } else {
        $main = new Professor();
    }

    if($mode == "updateClasses") {
        $data->assign("student", $main);
        if($main instanceof Student) {
            $smarty->display("classList.tpl", $data);
        } else {
            $smarty->display("profClassList.tpl", $data);
        }
    }

    if($mode == "getDepartmentData") {
        print $main->getDepartmentJSON();
    }

    if($mode == "getCourseData") {
        print $main->getCourseJSON($_REQUEST["dept"]);
    }
?>