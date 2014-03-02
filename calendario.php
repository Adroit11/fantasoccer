<?php

include_once dirname(__FILE__) . "/engine/engine.php";
//include_once( "engine/engine.php" );

$cal = mysql_query("SELECT giornata,squadra1,squadra2,punti1,punti2,gol1,gol2,risultato1,risultato2,torneo,g.timelimit FROM calendario AS c JOIN giornate AS g ON c.giornata=g.n ORDER BY giornata", $mysql);
if(!$cal) system_error("Errore nella connessione al DB");
    close_db();
while($r = mysql_fetch_row($cal)) {
    if($r[9]==true)
        $cal_tour[$r[0]][] = array($r[0],$r[1],$r[2],$r[3],$r[4],$r[5],$r[6],$r[7],$r[8], $r[10]);
    else
        $cal_champ[$r[0]][] = array($r[0],$r[1],$r[2],$r[3],$r[4],$r[5],$r[6],$r[7],$r[8], $r[10]);
}
if(!empty($cal_champ))
    $cal_champ = array_merge($cal_champ);
if(!empty($cal_tour))
    $cal_tour = array_merge($cal_tour);
unset($cal);

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
            <div id="colTwo" style="padding-right: 0px; padding-left: 0px; width: 690px;">
                <div>
                    <h2 align="center">Calendario campionato</h2>
                    
                    <!-- Modifica 2013-2014 --> 
                    <table align="center" class="giornata" style="width: auto;">
                        <thead>
                            <tr><th colspan="2">Legenda</th></tr>
                        </thead>
                        <tbody>
                            <tr><td style="font-weight: bold;">[C]</td><td>Giornata di campionato scontri diretti</td></tr>
                            <tr><td style="font-weight: bold;">[N° Pt]</td><td>Giornata del N-simo mini-girone a punti</td></tr>
                        </tbody>
                    </table>
                    <br/>
                    <!---->
                    
                    <table align="center" style="width: 100%;">
                        <?  for($i=0; $i<count($cal_champ); $i=$i+3) { ?>
                        <tr>
                            <? for($j=0; $j<3; $j++) {
                                if(!empty($cal_champ[$i+$j])) { ?>
                            <td>
                                <table align="center" class="giornata">
                                    <thead>
                                        <tr>
                                            <th class="top" colspan="8">
                                                <a href="archivio_formazioni.php?n=<?= $cal_champ[$i+$j][0][0]?>">
                                                    <? echo $cal_champ[$i+$j][0][0] ?>
                                                    
                                                    <!-- Modifica 2013-2014 -->               
                                                    <span style="font-size: 80%; font-weight: normal; color: dimgrey;">
                                                        <? if($cal_champ[$i+$j][0][0] < 38) { echo "[C]"; } ?>
                                                        <?= " [" . (intval(($i+$j)/9) + 1) . "° Pt]" ?>
                                                    </span>
                                                    <!---->
                                                    
                                                </a>
                                            </th>
                                        </tr>
                                        <tr>
                                            <?
                                            $d = new DateTime($cal_champ[$i+$j][0][9]);
                                            ?>
                                            <th colspan="2" style="text-align: center; font-weight: bold; font-size: 9px;"><?= date_format($d, 'd/m/Y H:i') ?></th>                        
                                            <th colspan="2" class="td_fields">Punti</th>            
                                            <th colspan="2" class="td_fields">Gol</th>            
                                            <th colspan="2" class="td_fields">Ris.</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <? for($k=0; $k<count($cal_champ[$i+$j]); $k++) { ?>
                                        <tr align="center">
                                            <td class="td_team"><a alt="Click per le statistiche!" href="stat_squadra.php?team=<? echo $cal_champ[$i+$j][$k][1] ?>"><? echo $cal_champ[$i+$j][$k][1] ?></a></td>
                                            <td class="td_team"><a alt="Click per le statistiche!" href="stat_squadra.php?team=<? echo $cal_champ[$i+$j][$k][2] ?>"><? echo $cal_champ[$i+$j][$k][2] ?></a></td>
                                            <td class="td_data"><? echo $cal_champ[$i+$j][$k][3] ?></td>
                                            <td class="td_data2"><? echo $cal_champ[$i+$j][$k][4] ?></td>
                                            <td class="td_data" style="font-weight: bold; color: #054195; background-color: #B0FB85;"><? echo $cal_champ[$i+$j][$k][5] ?></td>
                                            <td class="td_data2" style="font-weight: bold; color: #054195; background-color: #B0FB85;"><? echo $cal_champ[$i+$j][$k][6] ?></td>
                                            <td class="td_data"><? echo $cal_champ[$i+$j][$k][7] ?></td>
                                            <td class="td_data"><? echo $cal_champ[$i+$j][$k][8] ?></td>
                                        </tr>
                                        <? } ?>
                                    </tbody>
                                </table>
                            </td>
                            <? 
                                }
                            } ?>
                        </tr>
                        <? } ?>
                    </table>
                </div>
                <br/><br/>
                <? if(!empty($cal_tour)) { ?>
                <div>
                    <h2 align="center">Calendario Mini Torneo</h2>
                    <table align="center">
                        <? for($i=0; $i<count($cal_tour); $i=$i+3) { ?>
                        <tr>
                            <? for($j=0; $j<3; $j++) {
                                if(!empty($cal_tour[$i+$j])) { ?>
                            <td>
                                <table align="center" class="giornata">
                                    <thead>
                                        <tr>
                                            <th class="td_top" colspan="4"><a href="http://localhost/FantaProject/archivio_formazioni.php?n=<?echo $cal_tour[$i+$j][0][0]?>"><?echo "Giornata ".$cal_tour[$i+$j][0][0]?></a></th>
                                        </tr>
                                        <tr>
                                            <th colspan="2"></th>
                                            <th colspan="2" class="td_fields">Punti</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <? for($k=0; $k<count($cal_tour[$i+$j]); $k++) { ?>
                                        <tr align="center">
                                            <td class="td_team"><a alt="Click per le statistiche!" href="stat_squadra.php?team=<? echo $cal_tour[$i+$j][$k][1] ?>"><? echo $cal_tour[$i+$j][$k][1] ?></a></td>
                                            <td class="td_team"><a alt="Click per le statistiche!" href="stat_squadra.php?team=<? echo $cal_tour[$i+$j][$k][2] ?>"><? echo $cal_tour[$i+$j][$k][2] ?></a></td>
                                            <td class="td_data" style="background-color: #B0FB85;"><? echo $cal_tour[$i+$j][$k][3] ?></td>
                                            <td class="td_data" style="background-color: #B0FB85;"><? echo $cal_tour[$i+$j][$k][4] ?></td>
                                        </tr>
                                        <? } ?>
                                    </tbody>
                                </table>
                            </td>
                            <? }
                    } ?>
                        </tr>
                        <? } ?>
                    </table>
                </div>
                <? } ?>
                <br/><br/>
            </div>
        </div>
        <div id="footer">
            <p>Copyright (c) 2009 <a href="http://www.andreamartelli.it">Andrea Martelli</a>. All rights reserved.</p>
        </div>
    </body>
</html>