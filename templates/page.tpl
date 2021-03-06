<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
    <head>
        {$DEBUG = true}
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
        <meta http-equiv="content-language" content="en"/>
        <meta http-equiv="Content-Style-Type" content="text/css"/>
        <meta http-equiv="Content-Script-Type" content="text/javascript"/>
        <meta name="language" content="en"/>
        <meta name="description" content="Helps LETU students figure out their class schedules"/>
        <meta name="keywords" content="LETU LeTourneau student schedule class classes"/>

        <title>LUSA SE</title>
        {if $DEBUG}
            <!-- cat screen.css chosen.css | java -jar yuicompressor-2.4.2.jar --type css > compiled.css -->
            <link rel="stylesheet" type="text/css" href="layout/screen.css" media="screen,projection"/>
            <link rel="stylesheet" type="text/css" href="layout/chosen.css" media="screen,projection"/>
        {else}
            <link rel="stylesheet" type="text/css" href="layout/compiled.css" media="screen, projection"/>
        {/if}
        <link rel="stylesheet" type="text/css" href="layout/print.css" media="print"/>
        {if $DEBUG}
            <!-- cat prototype-orig.js selectMultiple.js functions-orig.js | java -jar yuicompressor-2.4.2.jar --type js > compiled.js -->
            <script type="text/javascript" src="layout/prototype-orig.js"></script>
            <script type="text/javascript" src="layout/selectMultiple.js"></script>
            <script type="text/javascript" src="layout/functions-orig.js"></script>
        {else}
            <script type="text/javascript" src="layout/compiled.js"></script>
        {/if}
    </head>
    <body lang="en" onload="lusa.init();">
        <!--LUSA 2: A Dorm 41 Production-->
        <!--Developed by: Wharf-->
        <!--Design by: Shutter-->
        <!--QA and Lead Tester: Synk-->
        <!--Performance Consultants: Zoot, Gary Raduns-->
        <!--This code hates Tom Kelley-->
        <!--Special thanks to all of 41 and G2 for their suggestions, bug reports, patience, and encouragement!-->
        {block name="body"}{/block}
    </body>
</html>