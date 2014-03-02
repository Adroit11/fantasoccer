<?php

include_once dirname(__FILE__) . "/engine/engine.php";
//include_once( "engine/engine.php" );

$team = $_GET['t'];
$result = mysql_query("SELECT nome, squadra, ruolo FROM giocatori WHERE seriea='$team'");
close_db();

while($r = mysql_fetch_row($result))
    $gioc[$r[2]][] = array($r[0], $r[1]);

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<!--
Design by Free CSS Templates
http://www.freecsstemplates.org
Released for free under a Creative Commons Attribution 2.5 License

Site Engineering by Andrewww
-->

<html xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>FantaTorneo *New Generation*</title>
        <? @include("page_elements/scripts.php"); ?>
        <link rel="shortcut icon" href="pics/favicon.ico"/>
    </head>
    <body>
        <? @include("page_elements/header.php"); ?>
        <div id="content">
            <div id="colOne">
                <div id="logo"></div>
                <? @include("page_elements/login_box.php"); ?>
                <? @include("page_elements/menu_lat.php"); ?>
            </div>
            <div id="colTwo">
                <div class="box">
                    <h2 align="center"><?echo $team?></h2>
                    <table align="center">
                        <tr align="center">
                            <td colspan="3" style="font-weight: bold; font-style: italic;">Portieri</td>
                        </tr>
                        <? foreach($gioc['P'] as $g) { ?>
                        <tr align="center" style="background-color: #CEEEF3">
                            <td><img src="<? echo "teams/".$team.".gif" ?>" alt="<? echo $team ?>" title="<? echo $team ?>"/></td>
                            <td><a href="stat_giocatore.php?n=<? echo urlencode($g[0]) ?>" alt="Click x statistiche" style="color: #2D6199; text-decoration: none; font-weight: bold;"><? echo $g[0] ?></a></td>
                            <td><a class="linkrsgio" href="stat_squadra.php?team=<?echo $g[1]?>" alt="Click x statistiche"><?echo $g[1]?></a></td>
                        </tr>
                        <? } ?>
                        <tr align="center">
                            <td colspan="3" style="font-weight: bold; font-style: italic;">Difensori</td>
                        </tr>
                        <? foreach($gioc['D'] as $g) { ?>
                        <tr align="center" style="background-color: #C9FEBF">
                            <td><img src="<? echo "teams/".$team.".gif" ?>" alt="<? echo $team ?>" title="<? echo $team ?>"/></td>
                            <td><a href="stat_giocatore.php?n=<? echo urlencode($g[0]) ?>" alt="Click x statistiche" style="color: #2D6199; text-decoration: none; font-weight: bold;"><? echo $g[0] ?></a></td>
                            <td><a class="linkrsgio" href="stat_squadra.php?team=<?echo $g[1]?>" alt="Click x statistiche"><?echo $g[1]?></a></td>
                        </tr>
                        <? } ?>
                        <tr align="center">
                            <td colspan="3" style="font-weight: bold; font-style: italic;">Centrocampisti</td>
                        </tr>
                        <? foreach($gioc['C'] as $g) { ?>
                        <tr align="center" style="background-color: #FDFEBF">
                            <td><img src="<? echo "teams/".$team.".gif" ?>" alt="<? echo $team ?>" title="<? echo $team ?>"/></td>
                            <td><a href="stat_giocatore.php?n=<? echo urlencode($g[0]) ?>" alt="Click x statistiche" style="color: #2D6199; text-decoration: none; font-weight: bold;"><? echo $g[0] ?></a></td>
                            <td><a class="linkrsgio" href="stat_squadra.php?team=<?echo $g[1]?>" alt="Click x statistiche"><?echo $g[1]?></a></td>
                        </tr>
                        <? } ?>
                        <tr align="center">
                            <td colspan="3" style="font-weight: bold; font-style: italic;">Attaccanti</td>
                        </tr>
                        <? foreach($gioc['A'] as $g) { ?>
                        <tr align="center" style="background-color: #FEC3BF">
                            <td><img src="<? echo "teams/".$team.".gif" ?>" alt="<? echo $team ?>" title="<? echo $team ?>"/></td>
                            <td><a href="stat_giocatore.php?n=<? echo urlencode($g[0]) ?>" alt="Click x statistiche" style="color: #2D6199; text-decoration: none; font-weight: bold;"><? echo $g[0] ?></a></td>
                            <td><a class="linkrsgio" href="stat_squadra.php?team=<?echo $g[1]?>" alt="Click x statistiche"><?echo $g[1]?></a></td>
                        </tr>
                        <? } ?>
                    </table>
                </div>
            </div>
        </div>
        <div id="footer">
            <p>Copyright (c) 2009 <a href="http://www.andreamartelli.it">Andrea Martelli</a>. All rights reserved.</p>
        </div>
    </body>
</html>