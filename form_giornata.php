<?php

include_once dirname(__FILE__) . "/engine/engine.php";
//include_once( "engine/engine.php" );

$result = mysql_query("SELECT MIN(n) FROM giornate WHERE giocata=FALSE", $mysql);
if(!$result)
    system_error("Errore: impossibile ottenere la prossima giornata");
$result = mysql_fetch_row($result);
$giornata = $result[0];
//Squadre nell'ordine delle sfide di giornata
$teams = mysql_query("SELECT squadra1, squadra2 FROM calendario WHERE giornata=$giornata", $mysql);

$ALL = mysql_query("SELECT f.squadra, tipo, ruolo, seriea, f.nome, orario FROM giocatori AS g JOIN formazioni_temp AS f ON g.nome=f.nome WHERE orario=(SELECT MAX(orario) FROM formazioni_temp WHERE squadra=f.squadra)", $mysql);

if(!$ALL || !$teams) system_error("Errore: impossibile ottenere le formazioni delle squadre");
close_db();

while($r = mysql_fetch_row($ALL)) {
    $forms[$r[0]][$r[1]][$r[2]][] = array($r[3], $r[4]);
    $times[$r[0]] = $r[5];
}
while($r = mysql_fetch_row($teams)) {
    $squadre[] = $r[0];
    $squadre[] = $r[1];
}
unset($teams);
unset($ALL);
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
        <style type="text/css">
            .linkrsgio:hover {
                font-weight: normal;
            }
            .A:hover {
                font-weight: normal;
            }
            .D:hover {
                font-weight: normal;
            }
            .C:hover {
                font-weight: normal;
            }
            .A:hover {
                font-weight: normal;
            }
        </style>
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
                <div>
                    <h2>Formazioni - Giornata n° <?echo $giornata?></h2>
                    <h4 align="center" style="color: red; font-weight: bold;">Le formazioni visualizzate sono le ultime inserite in ordine cronologico. Se l'orario di inserimento &egrave; successivo al limite massimo consentito per questa giornata, sar&agrave; considerata l'ultima versione della formazione consegnata in tempo, qualora presente, altrimenti sarà considerata nulla!</h4>
                    <br/><br/>
                    <table align="center" style="width: 640px;">
                        <colgroup>
                            <col style="width: 50%;"></col>
                            <col style="width: 50%;"></col>
                        </colgroup>
                        <? for($k=0; $k<count($squadre); $k=$k+2) { ?>
                        <tr><td align="center" style="color: black; font-weight: bold; background-color: #5FD959"><a class="linksqrose" alt="Click per statistiche!" href="stat_squadra.php?team=<?echo $squadre[$k]?>"><?echo $squadre[$k]?></a></td><td align="center" style="color: black; font-weight: bold; background-color: #5FD959"><a class="linksqrose" alt="Click per statistiche!" href="stat_squadra.php?team=<?echo $squadre[$k+1]?>"><?echo $squadre[$k+1]?></a></td></tr>
                        <tr valign="top">
                            <? for($j=0; $j<2; $j++) {
                                if(!$forms[$squadre[$k+$j]]) { ?>
                                <td align="center"><h4>Formazione non inserita!</h4></td>
                                <? } else { ?>
                                <td>
                                    <table align="center" style="width: 100%; border-collapse: collapse;">
                                        <tr align="center"><td colspan="4" style="font-weight: bold; font-style: italic;">Portieri</td></tr>
                                        <tr valign="top">
                                            <td>
                                                <table style="width: 100%; border-collapse: collapse;">
                                                    <colgroup>
                                                        <col style="width: 20px;"></col>
                                                        <col></col>
                                                        <col></col>
                                                        <col></col>
                                                    </colgroup>
                                                    <tr class="P">
                                                        <td><a class="fotosq" href="serieateam.php?t=<?echo $forms[$squadre[$k+$j]]['TITOLARE']['P'][0][0]?>"><img src="<?echo "teams/".$forms[$squadre[$k+$j]]['TITOLARE']['P'][0][0].".gif"?>" alt="<?echo $forms[$squadre[$k+$j]]['TITOLARE']['P'][0][1]?>"/></a></td>
                                                        <td width="60"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($forms[$squadre[$k+$j]]['TITOLARE']['P'][0][1])?>"><?echo $forms[$squadre[$k+$j]]['TITOLARE']['P'][0][1]?></a></td>
                                                        <td align="center" width="20" style="color: <? echo $forms[$squadre[$k+$j]]['TITOLARE']['P'][0][2]>=0 ? 'blue' : 'red' ?>; font-weight: bold;"><?echo $forms[$squadre[$k+$j]]['TITOLARE']['P'][0][2]?></td>
                                                        <td align="center" width="20" style="color: <? echo $forms[$squadre[$k+$j]]['TITOLARE']['P'][0][3]>=0 ? 'blue' : 'red' ?>; font-weight: bold;"><?echo $forms[$squadre[$k+$j]]['TITOLARE']['P'][0][3]?></td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td>
                                                <table style="width: 100%; border-collapse: collapse;">
                                                    <colgroup>
                                                        <col style="width: 20px;"></col>
                                                        <col></col>
                                                        <col></col>
                                                        <col></col>
                                                    </colgroup>
                                                    <tr class="P">
                                                        <td><a class="fotosq" href="serieateam.php?t=<?echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['P'][0][0]?>"><img src="<?echo "teams/".$forms[$squadre[$k+$j]]['PRIMA_RISERVA']['P'][0][0].".gif"?>" alt="<?echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['P'][0][1]?>"/></a></td>
                                                        <td width="60"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($forms[$squadre[$k+$j]]['PRIMA_RISERVA']['P'][0][1])?>"><?echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['P'][0][1]?></a></td>
                                                        <td align="center" width="20" style="color: <? echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['P'][0][2]>=0 ? 'blue' : 'red' ?>; font-weight: bold;"><?echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['P'][0][2]?></td>
                                                        <td align="center" width="20" style="color: <? echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['P'][0][3]>=0 ? 'blue' : 'red' ?>; font-weight: bold;"><?echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['P'][0][3]?></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr align="center"><td colspan="4" style="font-weight: bold; font-style: italic;">Difensori</td></tr>
                                        <tr valign="top">
                                            <td>
                                                <table style="width: 100%; border-collapse: collapse;">
                                                    <colgroup>
                                                        <col style="width: 20px;"></col>
                                                        <col></col>
                                                        <col></col>
                                                        <col></col>
                                                    </colgroup>
                                                    <? for($i=0; $i<count($forms[$squadre[$k+$j]]['TITOLARE']['D']); $i++) { ?>
                                                    <tr class="D">
                                                        <td><a class="fotosq" href="serieateam.php?t=<?echo $forms[$squadre[$k+$j]]['TITOLARE']['D'][$i][0]?>"><img src="<?echo "teams/".$forms[$squadre[$k+$j]]['TITOLARE']['D'][$i][0].".gif"?>" alt="<?echo $forms[$squadre[$k+$j]]['TITOLARE']['D'][$i][1]?>"/></a></td>
                                                        <td width="60"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($forms[$squadre[$k+$j]]['TITOLARE']['D'][$i][1])?>"><?echo $forms[$squadre[$k+$j]]['TITOLARE']['D'][$i][1]?></a></td>
                                                        <td align="center" width="20" style="color: <? echo $forms[$squadre[$k+$j]]['TITOLARE']['D'][$i][2]>=0 ? 'blue' : 'red' ?>; font-weight: bold;"><?echo $forms[$squadre[$k+$j]]['TITOLARE']['D'][$i][2]?></td>
                                                        <td align="center" width="20" style="color: <? echo $forms[$squadre[$k+$j]]['TITOLARE']['D'][$i][3]>=0 ? 'blue' : 'red' ?>; font-weight: bold;"><?echo $forms[$squadre[$k+$j]]['TITOLARE']['D'][$i][3]?></td>
                                                    </tr>
                                                    <? } ?>
                                                </table>
                                            </td>
                                            <td>
                                                <table style="width: 100%; border-collapse: collapse;">
                                                    <colgroup>
                                                        <col style="width: 20px;"></col>
                                                        <col></col>
                                                        <col></col>
                                                        <col></col>
                                                    </colgroup>
                                                    <tr class="D">
                                                        <td><a class="fotosq" href="serieateam.php?t=<?echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['D'][0][0]?>"><img src="<?echo "teams/".$forms[$squadre[$k+$j]]['PRIMA_RISERVA']['D'][0][0].".gif"?>" alt="<?echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['D'][0][1]?>"/></a></td>
                                                        <td width="60"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($forms[$squadre[$k+$j]]['PRIMA_RISERVA']['D'][0][1])?>"><?echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['D'][0][1]?></a></td>
                                                        <td align="center" width="20" style="color: <? echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['D'][0][2]>=0 ? 'blue' : 'red' ?>; font-weight: bold;"><?echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['D'][0][2]?></td>
                                                        <td align="center" width="20" style="color: <? echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['D'][0][3]>=0 ? 'blue' : 'red' ?>; font-weight: bold;"><?echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['D'][0][3]?></td>
                                                    </tr>
                                                    <tr class="D">
                                                        <td><a class="fotosq" href="serieateam.php?t=<?echo $forms[$squadre[$k+$j]]['SECONDA_RISERVA']['D'][0][0]?>"><img src="<?echo "teams/".$forms[$squadre[$k+$j]]['SECONDA_RISERVA']['D'][0][0].".gif"?>" alt="<?echo $forms[$squadre[$k+$j]]['SECONDA_RISERVA']['D'][0][1]?>"/></a></td>
                                                        <td width="60"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($forms[$squadre[$k+$j]]['SECONDA_RISERVA']['D'][0][1])?>"><?echo $forms[$squadre[$k+$j]]['SECONDA_RISERVA']['D'][0][1]?></a></td>
                                                        <td align="center" width="20" style="color: <? echo $forms[$squadre[$k+$j]]['SECONDA_RISERVA']['D'][0][2]>=0 ? 'blue' : 'red' ?>; font-weight: bold;"><?echo $forms[$squadre[$k+$j]]['SECONDA_RISERVA']['D'][0][2]?></td>
                                                        <td align="center" width="20" style="color: <? echo $forms[$squadre[$k+$j]]['SECONDA_RISERVA']['D'][0][3]>=0 ? 'blue' : 'red' ?>; font-weight: bold;"><?echo $forms[$squadre[$k+$j]]['SECONDA_RISERVA']['D'][0][3]?></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr align="center"><td colspan="4" style="font-weight: bold; font-style: italic;">Centrocampisti</td></tr>
                                        <tr valign="top">
                                            <td>
                                                <table style="width: 100%; border-collapse: collapse;">
                                                    <colgroup>
                                                        <col style="width: 20px;"></col>
                                                        <col></col>
                                                        <col></col>
                                                        <col></col>
                                                    </colgroup>
                                                    <? for($i=0; $i<count($forms[$squadre[$k+$j]]['TITOLARE']['C']); $i++) { ?>
                                                    <tr class="C">
                                                        <td><a class="fotosq" href="serieateam.php?t=<?echo $forms[$squadre[$k+$j]]['TITOLARE']['C'][$i][0]?>"><img src="<?echo "teams/".$forms[$squadre[$k+$j]]['TITOLARE']['C'][$i][0].".gif"?>" alt="<?echo $forms[$squadre[$k+$j]]['TITOLARE']['C'][$i][1]?>"/></a></td>
                                                        <td width="60"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($forms[$squadre[$k+$j]]['TITOLARE']['C'][$i][1])?>"><?echo $forms[$squadre[$k+$j]]['TITOLARE']['C'][$i][1]?></a></td>
                                                        <td align="center" width="20" style="color: <? echo $forms[$squadre[$k+$j]]['TITOLARE']['C'][$i][2]>=0 ? 'blue' : 'red' ?>; font-weight: bold;"><?echo $forms[$squadre[$k+$j]]['TITOLARE']['C'][$i][2]?></td>
                                                        <td align="center" width="20" style="color: <? echo $forms[$squadre[$k+$j]]['TITOLARE']['C'][$i][3]>=0 ? 'blue' : 'red' ?>; font-weight: bold;"><?echo $forms[$squadre[$k+$j]]['TITOLARE']['C'][$i][3]?></td>
                                                    </tr>
                                                    <? } ?>
                                                </table>
                                            </td>
                                            <td>
                                                <table style="width: 100%; border-collapse: collapse;">
                                                    <colgroup>
                                                        <col style="width: 20px;"></col>
                                                        <col></col>
                                                        <col></col>
                                                        <col></col>
                                                    </colgroup>
                                                    <tr class="C">
                                                        <td><a class="fotosq" href="serieateam.php?t=<?echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['C'][0][0]?>"><img src="<?echo "teams/".$forms[$squadre[$k+$j]]['PRIMA_RISERVA']['C'][0][0].".gif"?>" alt="<?echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['C'][0][1]?>"/></a></td>
                                                        <td width="60"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($forms[$squadre[$k+$j]]['PRIMA_RISERVA']['C'][0][1])?>"><?echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['C'][0][1]?></a></td>
                                                        <td align="center" width="20" style="color: <? echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['C'][0][2]>=0 ? 'blue' : 'red' ?>; font-weight: bold;"><?echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['C'][0][2]?></td>
                                                        <td align="center" width="20" style="color: <? echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['C'][0][3]>=0 ? 'blue' : 'red' ?>; font-weight: bold;"><?echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['C'][0][3]?></td>
                                                    </tr>
                                                    <tr class="C">
                                                        <td><a class="fotosq" href="serieateam.php?t=<?echo $forms[$squadre[$k+$j]]['SECONDA_RISERVA']['C'][0][0]?>"><img src="<?echo "teams/".$forms[$squadre[$k+$j]]['SECONDA_RISERVA']['C'][0][0].".gif"?>" alt="<?echo $forms[$squadre[$k+$j]]['SECONDA_RISERVA']['C'][0][1]?>"/></a></td>
                                                        <td width="60"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($forms[$squadre[$k+$j]]['SECONDA_RISERVA']['C'][0][1])?>"><?echo $forms[$squadre[$k+$j]]['SECONDA_RISERVA']['C'][0][1]?></a></td>
                                                        <td align="center" width="20" style="color: <? echo $forms[$squadre[$k+$j]]['SECONDA_RISERVA']['C'][0][2]>=0 ? 'blue' : 'red' ?>; font-weight: bold;"><?echo $forms[$squadre[$k+$j]]['SECONDA_RISERVA']['C'][0][2]?></td>
                                                        <td align="center" width="20" style="color: <? echo $forms[$squadre[$k+$j]]['SECONDA_RISERVA']['C'][0][3]>=0 ? 'blue' : 'red' ?>; font-weight: bold;"><?echo $forms[$squadre[$k+$j]]['SECONDA_RISERVA']['C'][0][3]?></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr align="center"><td colspan="4" style="font-weight: bold; font-style: italic;">Attaccanti</td></tr>
                                        <tr valign="top">
                                            <td>
                                                <table style="width: 100%; border-collapse: collapse;">
                                                    <colgroup>
                                                        <col style="width: 20px;"></col>
                                                        <col></col>
                                                        <col></col>
                                                        <col></col>
                                                    </colgroup>
                                                    <? for($i=0; $i<count($forms[$squadre[$k+$j]]['TITOLARE']['A']); $i++) { ?>
                                                    <tr class="A">
                                                        <td><a class="fotosq" href="serieateam.php?t=<?echo $forms[$squadre[$k+$j]]['TITOLARE']['A'][$i][0]?>"><img src="<?echo "teams/".$forms[$squadre[$k+$j]]['TITOLARE']['A'][$i][0].".gif"?>" alt="<?echo $forms[$squadre[$k+$j]]['TITOLARE']['A'][$i][1]?>"/></a></td>
                                                        <td width="60"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($forms[$squadre[$k+$j]]['TITOLARE']['A'][$i][1])?>"><?echo $forms[$squadre[$k+$j]]['TITOLARE']['A'][$i][1]?></a></td>
                                                        <td align="center" width="20" style="color: <? echo $forms[$squadre[$k+$j]]['TITOLARE']['A'][$i][2]>=0 ? 'blue' : 'red' ?>; font-weight: bold;"><?echo $forms[$squadre[$k+$j]]['TITOLARE']['A'][$i][2]?></td>
                                                        <td align="center" width="20" style="color: <? echo $forms[$squadre[$k+$j]]['TITOLARE']['A'][$i][3]>=0 ? 'blue' : 'red' ?>; font-weight: bold;"><?echo $forms[$squadre[$k+$j]]['TITOLARE']['A'][$i][3]?></td>
                                                    </tr>
                                                    <? } ?>
                                                </table>
                                            </td>
                                            <td>
                                                <table style="width: 100%; border-collapse: collapse;">
                                                    <colgroup>
                                                        <col style="width: 20px;"></col>
                                                        <col></col>
                                                        <col></col>
                                                        <col></col>
                                                    </colgroup>
                                                    <tr class="A">
                                                        <td><a class="fotosq" href="serieateam.php?t=<?echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['A'][0][0]?>"><img src="<?echo "teams/".$forms[$squadre[$k+$j]]['PRIMA_RISERVA']['A'][0][0].".gif"?>" alt="<?echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['A'][0][1]?>"/></a></td>
                                                        <td width="60"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($forms[$squadre[$k+$j]]['PRIMA_RISERVA']['A'][0][1])?>"><?echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['A'][0][1]?></a></td>
                                                        <td align="center" width="20" style="color: <? echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['A'][0][2]>=0 ? 'blue' : 'red' ?>; font-weight: bold;"><?echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['A'][0][2]?></td>
                                                        <td align="center" width="20" style="color: <? echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['A'][0][3]>=0 ? 'blue' : 'red' ?>; font-weight: bold;"><?echo $forms[$squadre[$k+$j]]['PRIMA_RISERVA']['A'][0][3]?></td>
                                                    </tr>
                                                    <tr class="A">
                                                        <td><a class="fotosq" href="serieateam.php?t=<?echo $forms[$squadre[$k+$j]]['SECONDA_RISERVA']['A'][0][0]?>"><img src="<?echo "teams/".$forms[$squadre[$k+$j]]['SECONDA_RISERVA']['A'][0][0].".gif"?>" alt="<?echo $forms[$squadre[$k+$j]]['SECONDA_RISERVA']['A'][0][1]?>"/></a></td>
                                                        <td width="60"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($forms[$squadre[$k+$j]]['SECONDA_RISERVA']['A'][0][1])?>"><?echo $forms[$squadre[$k+$j]]['SECONDA_RISERVA']['A'][0][1]?></a></td>
                                                        <td align="center" width="20" style="color: <? echo $forms[$squadre[$k+$j]]['SECONDA_RISERVA']['A'][0][2]>=0 ? 'blue' : 'red' ?>; font-weight: bold;"><?echo $forms[$squadre[$k+$j]]['SECONDA_RISERVA']['A'][0][2]?></td>
                                                        <td align="center" width="20" style="color: <? echo $forms[$squadre[$k+$j]]['SECONDA_RISERVA']['A'][0][3]>=0 ? 'blue' : 'red' ?>; font-weight: bold;"><?echo $forms[$squadre[$k+$j]]['SECONDA_RISERVA']['A'][0][3]?></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <td style="color: red; font-weight: bold; font-size: 14px;" align="center" colspan="8">Ultimo ins. <?echo $times[$squadre[$k+$j]]?></td>
                                        </tr>
                                    </table>
                                </td>
                            <? }
                            } ?>
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