<?php
    $path = "./";
    require_once($path."Main.php");
    $data = $_REQUEST["data"];
    parse_str($data, $_REQUEST);

    Main::init();
    if(Main::isStudent()) {
        $main = new Student();
    } else {
        $main = new Professor();
    }
    $main->display();
?>