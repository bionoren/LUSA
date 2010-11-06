<?php
    require_once("Main.php");
    Main::init();
    $main = new Student();
    $main->printClassDropdown($_REQUEST["department"], $_REQUEST["selection"]);
?>