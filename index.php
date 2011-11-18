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

	error_reporting(E_ALL);
	date_default_timezone_set("America/Chicago");

	$path = "./";
	require_once($path."Main.php");
	require_once($path."smarty/Smarty.class.php");
	Main::init();

	if(Main::isStudent()) {
		$main = new Student();
	} else {
		$main = new Professor();
	}

	$smarty = new Smarty();
    $data = new Smarty_Data();
    $data->assign("main", $main);

    $smarty->display("index.tpl", $data);
?>
