<?php

include_once dirname(__FILE__) . "/engine/engine.php";
//include_once( "engine/engine.php" );

$teams = mysql_query("SELECT * FROM squadre ORDER BY nome", $mysql);
$all = mysql_query("SELECT nome, seriea, valore, ruolo, squadra FROM giocatori WHERE squadra IS NOT NULL", $mysql);
close_db();
if(!$teams || !$all) system_error("Errore: Impossibile ottenere le rose");
while($s = mysql_fetch_row($teams))
    $squadre[] = array($s[0], $s[1]);
while($r = mysql_fetch_row($all))
    $rose_temp[$r[4]][$r[3]][] = array($r[0], $r[1], $r[2], $r[3]);

if(isset($rose_temp)) 
{
    for($i=0; $i<count($squadre); $i++)
    $rose[$i] = $rose_temp[$squadre[$i][0]];
    unset($rose_temp);
}
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
        <link rel="shortcut icon" href="pics/favicon.ico"/>
        <? @include("page_elements/scripts.php"); ?>
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
                    <h2 align="center">Rose complete</h2>
                    <table align="center" id="rose">
                        <? for($i=0; $i<count($squadre); $i=$i+4) { ?>
                        <tr valign="top">
                            <? for($j=0; $j<4; $j++) {
                                $sum = 0;  ?>
                            <td width="154">
                                <table class="rosa" align="center" style="width: 100%;">
                                    <tr align="center" style="color: black; font-weight: bold; background-color: #5FD959"><td colspan="3"><a class="linksqrose" alt="Clicca x le statistiche!" href="stat_squadra.php?team=<? echo $squadre[$i+$j][0] ?>"><? echo $squadre[$i+$j][0] ?></a></td></tr>
                                    <tr align="center" style="font-weight: bold; font-style: italic"><td colspan="3"><? echo $squadre[$i+$j][1] ?></td></tr>
                                    <? if(!isset($rose) || !isset($rose[$i+$j])) { ?>
                                    <tr align="center"><td width="154" valign="top"><h4>Rosa non inserita!</h4></td></tr>
                                    <? } else { ?>
                                    <? foreach($rose[$i+$j]['P'] as $r) {
                                        $sum = $sum + $r[2];
                                        ?>
                                    <tr class="P" align="center">
                                        <td><a class="fotosq" href="serieateam.php?t=<?echo $r[1]?>"><img src="<? echo "teams/".$r[1].".gif" ?>" alt="<? echo $r[1] ?>" title="<? echo $r[1] ?>"/></a></td>
                                        <td><a class="linkrsgio" href="stat_giocatore.php?n=<? echo urlencode($r[0]) ?>" alt="Click x statistiche"><? echo $r[0] ?></a></td>
                                        <td><? echo $r[2] ?></td>
                                    </tr>
                                    <? } ?>
                                <? foreach($rose[$i+$j]['D'] as $r) {
                                    $sum = $sum + $r[2];
                                    ?>
                                    <tr class="D" align="center">
                                        <td><a class="fotosq" href="serieateam.php?t=<?echo $r[1]?>"><img src="<? echo "teams/".$r[1].".gif" ?>" alt="<? echo $r[1] ?>" title="<? echo $r[1] ?>"/></a></td>
                                        <td><a class="linkrsgio" href="stat_giocatore.php?n=<? echo urlencode($r[0]) ?>" alt="Click x statistiche"><? echo $r[0] ?></a></td>
                                        <td><? echo $r[2] ?></td>
                                    </tr>
                                    <? } ?>
                                <? foreach($rose[$i+$j]['C'] as $r) {
                                    $sum = $sum + $r[2];
                                    ?>
                                    <tr class="C" align="center">
                                        <td><a class="fotosq" href="serieateam.php?t=<?echo $r[1]?>"><img src="<? echo "teams/".$r[1].".gif" ?>" alt="<? echo $r[1] ?>" title="<? echo $r[1] ?>"/></a></td>
                                        <td><a class="linkrsgio" href="stat_giocatore.php?n=<? echo urlencode($r[0]) ?>" alt="Click x statistiche"><? echo $r[0] ?></a></td>
                                        <td><? echo $r[2] ?></td>
                                    </tr>
                                    <? } ?>
                                <? foreach($rose[$i+$j]['A'] as $r) {
                                    $sum = $sum + $r[2]; ?>
                                    <tr class="A" align="center">
                                        <td><a class="fotosq" href="serieateam.php?t=<?echo $r[1]?>"><img src="<? echo "teams/".$r[1].".gif" ?>" alt="<? echo $r[1] ?>" title="<? echo $r[1] ?>"/></a></td>
                                        <td><a class="linkrsgio" href="stat_giocatore.php?n=<? echo urlencode($r[0]) ?>" alt="Click x statistiche"><? echo $r[0] ?></a></td>
                                        <td><? echo $r[2] ?></td>
                                    </tr>
                                    <? } ?>
                                    <tr align="center">
                                        <td style="color: #688A9F">Budget: </td><td></td>
                                        <td style="font-weight: bold"><? echo 600-$sum; ?></td>
                                    </tr>
                                    <? } ?>
                                </table>
                            </td>
                            <? } ?>
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