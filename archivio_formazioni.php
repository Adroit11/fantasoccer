<?php

include_once dirname(__FILE__) . "/engine/engine.php";
//include_once( "engine/engine.php" );

// Prima giornata di campionato
$min = get_first_sportday();
// Ultima giornata giocata
$max = get_last_sportday();

if(!$_GET['n'] || $_GET['n']==0 || $_GET['n']==null) {
    if(!$max)
        $giornata = get_next_sportday();
    else
        $giornata = $max;
} else {
    $giornata = $_GET['n'];
}

$all_votes = mysql_query("SELECT * FROM voti WHERE giornata=$giornata AND nome IN (SELECT nome FROM formazioni WHERE giornata=$giornata)", $mysql);
$ALL = mysql_query("SELECT f.squadra, tipo, ruolo, seriea, f.nome FROM giocatori AS g JOIN formazioni AS f ON g.nome=f.nome WHERE giornata=$giornata", $mysql);

//Squadre nell'ordine delle sfide di giornata
$teams = mysql_query("SELECT squadra1, squadra2, punti1, punti2 FROM calendario WHERE giornata=$giornata", $mysql);
close_db();
if(!$ALL || !$all_votes)
    system_error("Errore: Impossibile ottenere elenco match");
while($r = mysql_fetch_row($teams)) {
    $squadre[] = $r[0];
    $squadre[] = $r[1];
    $punti[] = $r[2];
    $punti[] = $r[3];
}
while($r = mysql_fetch_row($all_votes))
    $voti[$r[1]] = array($r[3],$r[4]);    // Soltanto voto e bonus/malus
while($r = mysql_fetch_row($ALL))
    $forms[$r[0]][$r[1]][$r[2]][] = array($r[3], $r[4], $voti[$r[4]][0], $voti[$r[4]][1]);
unset($teams);
unset($ALL);
unset($all_votes);

// Calcolo modificatore difesa x ogni squadra
foreach($squadre as $s) {
    $bonus = 0;
    $voti_mod = null;
    $mod_count = 0;
    $p1 = $forms[$s]['TITOLARE']['P'][0][1];
    $p2 = $forms[$s]['PRIMA_RISERVA']['P'][0][1];
    if($voti[$p1]) {
        $voto_mod_p = $voti[$p1][0];
        $mod_count++;
    } else if($voti[$p2]) {
            $voto_mod_p = $voti[$p2][0];
            $mod_count++;
    }
    $zeros = 0;
    for($i=0; $i<count($forms[$s]['TITOLARE']['D']); $i++) {
        $nome = $forms[$s]['TITOLARE']['D'][$i][1];
        if($voti[$nome]) {
            $voti_mod[] = $voti[$nome][0];
            $mod_count++;
        }
        else
            $zeros++;
    }
    if($zeros==1) {
        $r1 = $forms[$s]['PRIMA_RISERVA']['D'][0][1];
        $r2 = $forms[$s]['SECONDA_RISERVA']['D'][0][1];
        if($voti[$r1]) {
            $voti_mod[] = $voti[$r1][0];
            $mod_count++;
        } else if($voti[$r2]) {
                $voti_mod[] = $voti[$r2][0];
                $mod_count++;
        }
    }
    else if($zeros>1) {
            $r1 = $forms[$s]['PRIMA_RISERVA']['D'][0][1];
            $r2 = $forms[$s]['SECONDA_RISERVA']['D'][0][1];
            if($voti[$r1]) {
                $voti_mod[] = $voti[$r1][0];
                $mod_count++;
            }
            if($voti[$r2]) {
                $voti_mod[] = $voti[$r2][0];
                $mod_count++;
            }
    }
    if(count($forms[$s]['TITOLARE']['D'])>=4) {   // Modificatore Difesa: (solo se giocano almeno 4 dif.)
        rsort($voti_mod);                           // ordino i voti dei difensori in ordine decrescente
        $best = array_slice($voti_mod, 0, 3);       // ne prendo i migliori 3 e
        $tot = ($voto_mod_p + array_sum($best))/4;  // sommo al voto del portiere
        if($tot<6) $bonus = 0;
        else if($tot>=6 && $tot<6.5) $bonus = 1;
            else if($tot>=6.5 && $tot<7) $bonus = 3;
                else if($tot>=7) $bonus = 6;
        $modificatori[$s] = $bonus;
    }
}

unset($voti);

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
                    <h2>Archivio formazioni</h2>
                    <? if(!$max || $max==0) { ?>
                    <h4>Non &egrave; stata ancora giocata nessuna giornata</h4>
                    <? } else { ?>
                    <h3 align="center">Giornata n° <?echo $giornata?></h3>
                    <br/>
                    <div align="center" style="font-size: 14px;">
                        <? for($i = $min; $i <= $max; $i++) { ?>
                        <a href="archivio_formazioni.php?n=<?echo $i?>"><?echo $i." "?></a>
                        <? } ?>
                    </div>
                    <br/>
                    <table align="center" style="width: 640px;">
                        <colgroup>
                            <col style="width: 50%;"></col>
                            <col style="width: 50%;"></col>
                        </colgroup>
                        <? for($k=0; $k<count($squadre); $k=$k+2) { ?>
                        <tr><td align="center" style="color: black; font-weight: bold; background-color: #5FD959"><a class="linksqrose" alt="Click per statistiche!" href="stat_squadra.php?team=<?echo $squadre[$k]?>"><?echo $squadre[$k]?></a></td><td align="center" style="color: black; font-weight: bold; background-color: #5FD959"><a class="linksqrose" alt="Click per statistiche!" href="stat_squadra.php?team=<?echo $squadre[$k]?>"><?echo $squadre[$k+1]?></a></td></tr>
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
                                        <? if(isset($modificatori[$squadre[$k+$j]])) { ?>
                                            <tr>
                                                <td style="font-weight: bold; font-style: italic; color: #0066FF;">Mod. difesa: </td>
                                                <td align="center" style="text-align: center; color: #0066FF; font-weight: bold; font-size: 14px;"><? echo $modificatori[$squadre[$k+$j]] ?></td>
                                            </tr>
                                        <? } ?>
                                        <tr>
                                            <td style="font-weight: bold; font-style: italic; color: #0066FF;">Tot. Punti: </td>
                                            <td align="center" style="text-align: center; color: #0066FF; font-weight: bold; font-size: 16px;"><? echo $punti[$k+$j] ?></td>
                                        </tr>
                                    </table>
                                </td>
                            <? }
                            } ?>
                        </tr>
                        <? } ?>
                    </table>
                    <? } ?>
                </div>
            </div>
        </div>
        <div id="footer">
            <p>Copyright (c) 2009 <a href="http://www.andreamartelli.it">Andrea Martelli</a>. All rights reserved.</p>
        </div>
    </body>
</html>