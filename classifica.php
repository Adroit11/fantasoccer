<?php

include_once dirname(__FILE__) . "/engine/engine.php";

$result_champ = mysql_query("SELECT giornata,squadra1,squadra2,punti1,punti2,gol1,gol2,risultato1,risultato2
                       FROM calendario WHERE giornata IN (SELECT n FROM giornate WHERE giocata=1 AND torneo=0)
                       ORDER BY GIORNATA", $mysql);

$result_tour = mysql_query("SELECT giornata,squadra1,squadra2,punti1,punti2
                       FROM calendario WHERE giornata IN (SELECT n FROM giornate WHERE giocata=1 AND torneo=1)
                       ORDER BY GIORNATA", $mysql);

$result_round = mysql_query("");

$num_teams = mysql_query("SELECT COUNT(*) FROM squadre");
$num_teams = mysql_fetch_row($num_teams);
$num_teams = $num_teams[0];

if(!$result_champ && !$result_tour) system_error("Errore nella connessione al DB");
close_db();

// --- MINI-TORNEO (inizio) --- //
while($r = mysql_fetch_row($result_tour)) {
    $tour[$r[1]][$r[0]] = $r[3];    // [Squadra][Giornata]--> Punti effettivi
    $tour[$r[2]][$r[0]] = $r[4];
}
// Ordinamento classifica finale Mini-torneo
if($result_tour && $tour) {
    $keys = array_keys($tour);
    $N2 = count($tour[$keys[0]]);
    // Calcolo i punti totali
    foreach($tour as $t => $v)
        foreach($v as $g => $p)
            $last_tour[$t] += $p;
    // Ordinamento
    foreach($last_tour as $t => $p)
        $tour_class[] = array($t, $p);
    for($i = 0; $i < count($tour_class) - 1; $i++) {
        $max = $tour_class[$i][1];
        $imax = $i;
        for($j = $i + 1; $j < count($tour_class); $j++) {
            if($tour_class[$j][1] > $max) {
                $max = $tour_class[$j][1];
                $imax = $j;
            }
        }
        $temp = $tour_class[$i];
        $tour_class[$i] = $tour_class[$imax];
        $tour_class[$imax] = $temp;
    }
    unset($temp);
}
// --- MINI-TORNEO (fine) --- //

// --- CAMPIONATO (inizio) --- //
$count = 0;
$prev = 0;
$roundTrends = array();
$n_played = 0;
while($r = mysql_fetch_row($result_champ)) {
    $n_played++;
    $v1=0; $v2=0; $pa1=0; $pa2=0; $s1=0; $s2=0;
    if($r[5]>$r[6])
    {
        $p1 = 3; $v1=1;
        $p2 = 0; $s2=1;
    }
    else if($r[6]>$r[5])
    {
        $p1 = 0; $s1 = 1;
        $p2 = 3; $v2 = 1;
    }
    else
    {
        $p1 = 1; $pa1 = 1;
        $p2 = 1; $pa2 = 1;
    }
    
    /* Aggiunta 2013-2014 */
    // Lista dei punteggi di ogni squadra divisa in gironi da 9 giornate
    $nRound = intval(($r[0] - $firstDay) / 9);
    $roundTrends[$nRound][$r[1]][] = $r[3];
    $roundTrends[$nRound][$r[2]][] = $r[4];
    /* ------------------ */
    
    // Campionato normale a scontri diretti
    // [Squadra][Giornata]--> GF, GS, Pt, V, P, S, Ris
    if($r[0] != 38) /* 2013-2014 */
    {
        $champ[$r[1]][$r[0]] = array($r[5]+$champ[$r[1]][$prev][0], $r[6]+$champ[$r[1]][$prev][1],
            $p1+$champ[$r[1]][$prev][2],$v1+$champ[$r[1]][$prev][3],$pa1+$champ[$r[1]][$prev][4],$s1+$champ[$r[1]][$prev][5],$r[7]);
        $champ[$r[2]][$r[0]] = array($r[6]+$champ[$r[2]][$prev][0],$r[5]+$champ[$r[2]][$prev][1],
            $p2+$champ[$r[2]][$prev][2],$v2+$champ[$r[2]][$prev][3],$pa2+$champ[$r[2]][$prev][4],$s2+$champ[$r[2]][$prev][5],$r[8]);
    }

    $count += 2;
    if($r[0] != $prev && $count == $num_teams) {
        $prev = $r[0];
        $count = 0;
    }
}
$n_played /= ($num_teams / 2);

/* Aggiunta 2013-2014 */
$roundFinals = array();
for($i = 0; $i < count($roundTrends); $i++)  // per ogni girone
{
    foreach($roundTrends[$i] as $t => $v)    // per ogni squadra
    {
        for($j = 0; $j < count($roundTrends[$i][$t]); $j++)  // per ogni giornata
        {
            $roundTrends[$i][$t][$j] += $roundTrends[$i][$t][$j - 1]; // round, squadra, giornata
            if($j == count($roundTrends[$i][$t]) - 1)    // ultima giornata del girone a punti
            {
                $roundFinals[$i][$t] = $roundTrends[$i][$t][$j];    // roundFinals[girone][squadra]
            }
        }
    }
    arsort($roundFinals[$i]);
}
/* ------------------ */

// Calcolo totali e ordinamento classifica finale Campionato (ordinamento dell'ultima giornata)
if($champ) {
    $keys = array_keys($champ);
    
    $N = count($champ[$keys[0]]);   // ultima giornata giocata
    $N = $prev;
    
    foreach($champ as $t=>$v)             // Presuppone somma corretta progressiva in $champ
    // (squadra, punti, GF, GS, V, P, S)
        $last[] = array($t,$v[$N][2],$v[$N][0],$v[$N][1],$v[$N][3],$v[$N][4],$v[$N][5]);

    for($i=0; $i<count($last)-1; $i++) {
        $maxpt = $last[$i][1];     // Punti
        $maxv = $last[$i][4];      // Vittorie
        $maxg = $last[$i][2];      // Gol fatti
        $imax = $i;
        for($j=$i+1; $j<count($last); $j++) {
            if($last[$j][1] > $maxpt) {         // ordine x punti
                $maxpt = $last[$j][1];
                $imax = $j;
            } else if($last[$j][1] == $maxpt) {
                    if($last[$j][4] > $maxv) {     // ordine per vittorie
                        $maxv = $last[$j][4];
                        $imax = $j;
                    } else if($last[$j][4] == $maxv) {
                            if($last[$j][2] > $maxg) { // ordine per gol fatti
                                $maxg = $last[$j][2];
                                $imax = $j;
                            }
                        }
                }
        }
        $temp = $last[$i];
        $last[$i] = $last[$imax];
        $last[$imax] = $temp;
    }
}

/* Generazione serie dati per grafico Morris.js */

$teams = array_keys($champ);
$days = array_keys($champ[$teams[0]]);
$data = array();
foreach($days as $d)
{
    $dobj = array();
    $dobj['y'] = strval($d);
    foreach($teams as $t)
    {
        $dobj[$t] = $champ[$t][$d][2];
    }    
    
    $data[] = $dobj;
}
$options['element'] = 'chart-classifica';
$options['data'] = $data;
$options['xkey'] = 'y';
$options['ykeys'] = $teams;
$options['labels'] = $teams;
$options['hideHover'] = true;

$morrisjs_config = json_encode($options);
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
        <? @include("page_elements/scripts_updated.php"); ?>
        <link href="<?= $baseUrl ?>javascript/morris/morris.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="<?= $baseUrl ?>javascript/raphael-min.js"></script>
        <script type="text/javascript" src="<?= $baseUrl ?>javascript/morris/morris.min.js"></script>
        <script type="text/javascript">
            $(document).ready(function() {
                $("#class_camp").delegate('td','mouseover mouseleave', function(e) {
                    if($(this).index() != 0) {
                        var el = $(this);
                        if (e.type == 'mouseover') {
                            $("#class_camp col").eq($(this).index()).addClass("classcol");
                        }
                        else {
                            $("#class_camp col").eq($(this).index()).removeClass("classcol");
                        }
                    }
                });

                $("#and_camp").delegate('td','mouseover mouseleave', function(e) {
                    if($(this).index() != 0) {
                        var el = $(this);
                        if (e.type == 'mouseover') {
                            $("#and_camp col").eq($(this).index()).addClass("classcol");
                        }
                        else {
                            $("#and_camp col").eq($(this).index()).removeClass("classcol");
                        }
                    }
                });
                
                // Creazione grafico con Morris.js
                Morris.Line(JSON.parse('<?= $morrisjs_config ?>'));
            });
        </script>
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
                <!-- Classifica Campionato -->
                <h2 align="center">Campionato</h2>
                
                <h3 align="left">Classifica</h3><br/>
                <table class="classifica" align="center" id="class_camp">
                    <colgroup>
                        <col class="first"></col>
                        <col></col>
                        <col></col>
                        <col></col>
                        <col></col>
                        <col></col>
                        <col></col>
                        <col></col>
                        <col></col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Squadra</th>
                            <th>Punti</th>
                            <th>GF</th>
                            <th>GS</th>
                            <th>Diff. reti</th>
                            <th>G</th>
                            <th>V</th>
                            <th>P</th>
                            <th>S</th>
                        </tr>
                    </thead>
                    <? for($i=0; $i<count($last); $i++) { ?>
                    <tr>
                        <td width="130px"><a alt="Click per statistiche!" href="stat_squadra.php?team=<?= $last[$i][0]?>"><?= $last[$i][0]?></a></td>
                        <td width="60px" style="font-size: 16px; font-weight: bold; color: blue; font-style: italic;"><?= $last[$i][1]?></td>
                        <td width="35px"><?= $last[$i][2]?></td>
                        <td width="35px"><?= $last[$i][3]?></td>
                        <td width="60px"><?= $last[$i][2]-$last[$i][3]?></td>
                        <td width="35px"><?= $n_played ?></td>
                        <td width="35px"><?= $last[$i][4]?></td>
                        <td width="35px"><?= $last[$i][5] ?></td>
                        <td width="35px"><?= $last[$i][6]?></td>
                    </tr>
                    <? } ?>
                </table>
                <!-- Grafico andamento campionato (FusionCharts) -->
                <br/>
                
                <div id="chart-classifica" style="width: 617px; height: 400px;"></div>
                    
                <!-- Grafico andamento campionato -->
                <br/>
                <!-- 1.a) Statistiche Campionato (serie risultati) -->
                <h3 align="left">Serie risultati</h3><br/>
                <table align="center" class="classifica" id="and_camp">
                    <colgroup>
                        <col class="first"></col>
                        <? if($champ)
                            foreach($champ[$keys[0]] as $gg => $v) { ?>
                                <col></col>
                        <? } ?>
                    </colgroup>
                    <thead>
                        <th>Squadra</th>
                        <? if($champ)
                            foreach($champ[$keys[0]] as $gg => $v) { ?>
                                <th><?= $gg?></th>
                        <? } ?>
                    </thead>
                    <tbody>
                        <? if($champ) {
                        foreach($champ as $sq=>$v) { ?>
                        <tr>
                            <td width="130px"><a alt="Click per statistiche!" href="stat_squadra.php?team=<?= $sq ?>"><?= $sq ?></a></td>
                            <? foreach($champ[$sq] as $gg => $v) { ?>
                                <td class="data"><?= $v[6] ?></td>
                            <? } ?>
                        </tr>
                        <? }
                        } ?>
                    </tbody>
                </table>

                <!-- Classifica Mini-Torneo -->
                <br/>
                <? if($last_tour) { ?>
                <h2 align="center">Mini Torneo</h2>
                <h3 align="left">Classifica</h3><br/>
                <table align="center" class="classifica">
                    <thead>
                        <tr>
                            <th>Squadra</th>
                            <th>Punti</th>
                            <th>Giocate</th>
                        </tr>
                    </thead>
                    <? foreach($tour_class as $index => $team_array) { ?>
                    <tr>
                        <td class="team" width="130px"><?echo $team_array[0] ?></td>
                        <td width="40px" class="data"  style="font-size: 16px; color: blue; font-style: italic;"><?echo $team_array[1] ?></td>
                        <td width="25px" class="data"><?echo $N2?></td>
                    </tr>
                    <? } ?>
                </table><br/>
                <!-- 2.a) Statistiche Mini-Torneo -->
                <h3 align="left">Serie punteggi</h3><br/>
                <table align="center" class="classifica">
                    <thead>
                        <th>Squadra</th>
                        <? foreach($tour[$keys[0]] as $gg=>$v) { ?>
                            <th><?echo $gg?></th>
                        <? } ?>
                    </thead>
                    <? foreach($tour as $sq=>$v) { ?>
                    <tr>
                        <td class="team"><?echo $sq?></td>
                        <? foreach($tour[$sq] as $$gg=>$v) { ?>
                            <td class="data"><?echo $v?></td>
                        <? } ?>
                    </tr>
                    <? } ?>
                </table>
                <? } ?>
                <br/>
                
                <!-- Aggiunta 2013-2014 -->
                <!-- Classifica Gironi a punti -->
                <? if(isset($roundFinals) && !empty($roundFinals)) { ?>
                
                    <h2 align="center">Gironi a punteggio</h2>
                
                    <? for($i = 0; $i < count($roundFinals); $i++) { ?>

                    <h3 align="left"><?= ($i+1) ?>° Girone</h3><br/>
                    <table class="classifica" align="center" id="class_camp">
                        <colgroup>
                            <col class="first"></col>
                            <col></col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Squadra</th>
                                <th>Punteggio</th>
                            </tr>
                        </thead>
                        <? foreach($roundFinals[$i] as $t => $pts) { ?>
                        <tr>
                            <td width="130px"><a alt="Click per statistiche!" href="stat_squadra.php?team=<?= $t ?>"><?= $t ?></a></td>
                            <td width="60px" style="font-size: 16px; font-weight: bold; color: blue; font-style: italic;"><?= $pts ?></td>
                        </tr>
                        <? } ?>
                    </table>

                    <? } ?>
                
                <? } ?>
                
                <!-- end -->
            <br/><br/>
            </div>
        </div>
        <br/><br/>
        <div id="footer">
            <p>Copyright (c) 2009 <a href="http://www.andreamartelli.it">Andrea Martelli</a>. All rights reserved.</p>
        </div>
    </body>
</html>
