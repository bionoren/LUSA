<?php
    $path = "./";
    require_once($path."Main.php");
    $data = $_REQUEST["data"];
    parse_str($data, $_REQUEST);
    $_REQUEST["submit"] = true;
    parse_str($data, $_POST);
    $_POST["submit"] = true;

    Main::init();
    $main = new Student();
    $main->displaySchedules();
?>