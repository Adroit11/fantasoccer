<?php

include_once dirname(__FILE__) . "/engine/engine.php";
//include_once( "engine/engine.php" );

if($_GET['s']=="r")
    $ord = "ruolo";
else if($_GET['s']=="s")
    $ord = "seriea";
else $ord = "nome";
if($_GET['l']) {
    $lett = $_GET['l'];
    $result = mysql_query("SELECT seriea, nome, ruolo FROM giocatori WHERE nome LIKE '".$lett."%' ORDER BY $ord");
} else {
    $result = mysql_query("SELECT seriea, nome, ruolo FROM giocatori WHERE nome LIKE 'A%' ORDER BY $ord");
}
close_db();

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
                <h2>Elenco giocatori</h2>
                <div align="center" id="pager">
                <? foreach(range('A','Z') as $i) { ?>
                    <a style="font-size: 16px;" href="elenco_giocatori.php?l=<?echo $i?>"><?echo $i?></a>
                <? } ?>
                </div>
                <br/><br/>
                <table align="center" style="width: 300px; border-collapse: collapse;">
                    <colgroup>
                        <col style="width: 20px;"></col>
                        <col></col>
                        <col style="width: 20px;"></col>
                    </colgroup>
                    <thead>
                        <th><a title="Ordina per squadra" name="Ordina per squadra" href="elenco_giocatori.php?l=<?echo isset($lett) ? $lett : 'A'?>&amp;s=s">Squadra</a></th>
                        <th><a title="Ordina per nome" name="Ordina per nome" href="elenco_giocatori.php?l=<?echo isset($lett) ? $lett : 'A'?>">Nome</a></th>
                        <th><a title="Ordina per ruolo" name="Ordina per ruolo" href="elenco_giocatori.php?l=<?echo isset($lett) ? $lett : 'A'?>&amp;s=r">Ruolo</a></th>
                    </thead>
                    <? while($r = mysql_fetch_row($result)) { ?>
                    <tr class="<?= $r[2] ?>" align="center">
                        <td><a class="fotosq" href="serieateam.php?t=<?echo $r[0]?>"><img src="<? echo "teams/".$r[0].".gif" ?>" alt="<? echo $r[0] ?>" title="<? echo $r[0] ?>"/></a></td>
                        <td style="font-size: 14px;" width="120px"><a class="linkrsgio" href="stat_giocatore.php?n=<? echo urlencode($r[1]) ?>" alt="Click x statistiche"><?echo $r[1]?></a></td>
                        <td><?echo $r[2]?></td>
                       </tr>
                    <? } ?>
                </table>
                <br/><br/>
            </div>
        </div>
        <div id="footer">
            <p>Copyright (c) 2009 <a href="http://www.andreamartelli.it">Andrea Martelli</a>. All rights reserved.</p>
        </div>
    </body>
</html>
