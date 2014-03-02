<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

include_once dirname(__FILE__) . "/engine.php";
//include_once("engine.php");

//gatekeeper();

$message = $_GET['message'];

?>

<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>FantaTorneo *New Generation*</title>
        <link href="<? echo $baseUrl ?>style.css" rel="stylesheet" type="text/css" />
        <link rel="shortcut icon" href="pics/favicon.ico"/>
    </head>
    <body>
        <? include("../page_elements/header.php"); ?>
        <div id="content">
            <div id="colOne">
                <div id="logo"></div>
                <? include("../page_elements/login_box.php"); ?>
                <? include("../page_elements/menu_lat.php"); ?>
            </div>
            <div id="colTwo">
                <div class="box">
                    <h2>Errore del sistema</h2>
                    <p class="bottom">
                        <strong style="color: red;"><? echo $message; ?></strong>
                    </p>
                </div>
            </div>
        </div>
        <div id="footer">
            <p>Copyright (c) 2009 <a href="http://www.andreamartelli.it">Andrea Martelli</a>. All rights reserved.</p>
        </div>
    </body>
</html>
