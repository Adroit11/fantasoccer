<?php

include_once dirname(__FILE__) . "/engine/engine.php";
//include_once( "engine/engine.php" );

$result_champ = mysql_query("SELECT giornata,squadra1,squadra2,punti1,punti2,gol1,gol2,risultato1,risultato2
                       FROM calendario WHERE giornata IN (SELECT n FROM giornate WHERE giocata=1 AND torneo=0)
                       ORDER BY GIORNATA", $mysql);

$num_teams = mysql_query("SELECT COUNT(*) FROM squadre");
$num_teams = mysql_fetch_row($num_teams);
$num_teams = $num_teams[0];

$top_p = mysql_query("SELECT DISTINCT g.squadra,g.seriea,v.nome,AVG(voto) AS avg_voto,AVG(voto+bonusmalus) FROM voti AS v JOIN giocatori AS g ON v.nome=g.nome WHERE g.ruolo='P' GROUP BY nome ORDER BY avg_voto DESC LIMIT 0,5");
$flop_p = mysql_query("SELECT DISTINCT g.squadra,g.seriea,v.nome,AVG(voto) AS avg_voto,AVG(voto+bonusmalus) FROM voti AS v JOIN giocatori AS g ON v.nome=g.nome WHERE g.ruolo='P' GROUP BY nome ORDER BY avg_voto ASC LIMIT 0,5");
$top_d = mysql_query("SELECT DISTINCT g.squadra,g.seriea,v.nome,AVG(voto) AS avg_voto,AVG(voto+bonusmalus) FROM voti AS v JOIN giocatori AS g ON v.nome=g.nome WHERE g.ruolo='D' GROUP BY nome ORDER BY avg_voto DESC LIMIT 0,5");
$flop_d = mysql_query("SELECT DISTINCT g.squadra,g.seriea,v.nome,AVG(voto) AS avg_voto,AVG(voto+bonusmalus) FROM voti AS v JOIN giocatori AS g ON v.nome=g.nome WHERE g.ruolo='D' GROUP BY nome ORDER BY avg_voto ASC LIMIT 0,5");
$top_c = mysql_query("SELECT DISTINCT g.squadra,g.seriea,v.nome,AVG(voto) AS avg_voto,AVG(voto+bonusmalus) FROM voti AS v JOIN giocatori AS g ON v.nome=g.nome WHERE g.ruolo='C' GROUP BY nome ORDER BY avg_voto DESC LIMIT 0,5");
$flop_c = mysql_query("SELECT DISTINCT g.squadra,g.seriea,v.nome,AVG(voto) AS avg_voto,AVG(voto+bonusmalus) FROM voti AS v JOIN giocatori AS g ON v.nome=g.nome WHERE g.ruolo='C' GROUP BY nome ORDER BY avg_voto ASC LIMIT 0,5");
$top_a = mysql_query("SELECT DISTINCT g.squadra,g.seriea,v.nome,AVG(voto) AS avg_voto,AVG(voto+bonusmalus) FROM voti AS v JOIN giocatori AS g ON v.nome=g.nome WHERE g.ruolo='A' GROUP BY nome ORDER BY avg_voto DESC LIMIT 0,5");
$flop_a = mysql_query("SELECT DISTINCT g.squadra,g.seriea,v.nome,AVG(voto) AS avg_voto,AVG(voto+bonusmalus) FROM voti AS v JOIN giocatori AS g ON v.nome=g.nome WHERE g.ruolo='A' GROUP BY nome ORDER BY avg_voto ASC LIMIT 0,5");
$goleador = mysql_query("SELECT DISTINCT g.squadra,g.seriea,v.nome,SUM(gf) AS sum_gf FROM voti AS v JOIN giocatori AS g ON v.nome=g.nome GROUP BY nome ORDER BY sum_gf DESC LIMIT 0,10");
$assist = mysql_query("SELECT DISTINCT g.squadra,g.seriea,v.nome,SUM(assist) AS sum_ass FROM voti AS v JOIN giocatori AS g ON v.nome=g.nome GROUP BY nome ORDER BY sum_ass DESC LIMIT 0,10");
$ammoniti = mysql_query("SELECT DISTINCT g.squadra,g.seriea,v.nome,SUM(ammonizioni) AS sum_amm FROM voti AS v JOIN giocatori AS g ON v.nome=g.nome GROUP BY nome ORDER BY sum_amm DESC LIMIT 0,5");
$espulsi = mysql_query("SELECT DISTINCT g.squadra,g.seriea,v.nome,SUM(espulsioni) AS sum_esp FROM voti AS v JOIN giocatori AS g ON v.nome=g.nome GROUP BY nome ORDER BY sum_esp DESC LIMIT 0,5");

if(!$result_champ || !$num_teams || !$top_a || !$top_c || !$top_d || !$top_p || !$flop_a || !$flop_c || !$flop_d || !$flop_p || !$goleador || !$assist || !$ammoniti || !$espulsi)
    system_error("Errore: Impossibile ottenere le statistiche del campionato");

close_db();

// --- CAMPIONATO (inizio) --- //
$count = 0;
$prev = 0;
while($r = mysql_fetch_row($result_champ)) {
    $v1=0; $v2=0; $pa1=0; $pa2=0; $s1=0; $s2=0;
    if($r[5]>$r[6]) {
        $p1 = 3; $v1=1;
        $p2 = 0; $s2=1;
    } else if($r[6]>$r[5]) {
            $p1 = 0; $s1 = 1;
            $p2 = 3; $v2 = 1;
        } else {
            $p1 = 1; $pa1 = 1;
            $p2 = 1; $pa2 = 1;
        }
    // [Squadra][Giornata]--> GF, GS, Pt, V, P, S, Ris, Punti base

    $champ[$r[1]][$r[0]] = array($r[5]+$champ[$r[1]][$prev][0], $r[6]+$champ[$r[1]][$prev][1],
            $p1+$champ[$r[1]][$prev][2],$v1+$champ[$r[1]][$prev][3],$pa1+$champ[$r[1]][$prev][4],$s1+$champ[$r[1]][$prev][5],$r[7],
            $r[3] + $champ[$r[1]][$prev][6]);

    $champ[$r[2]][$r[0]] = array($r[6]+$champ[$r[2]][$prev][0],$r[5]+$champ[$r[2]][$prev][1],
        $p2+$champ[$r[2]][$prev][2],$v2+$champ[$r[2]][$prev][3],$pa2+$champ[$r[2]][$prev][4],$s2+$champ[$r[2]][$prev][5],$r[8],
        $r[4] + $champ[$r[2]][$prev][6]);

    $count += 2;
    if($r[0] != $prev && $count == $num_teams) {
        $prev = $r[0];
        $count = 0;
    }
}
// Calcolo totali e ordinamento classifica finale Campionato (ordinamento dell'ultima giornata)
if($champ) {
    $keys = array_keys($champ);
    $N = count($champ[$keys[0]]);   // ultima giornata giocata
    $N = $prev;
    foreach($champ as $t=>$v)             // Presuppone somma corretta progressiva in $champ
    // (squadra, punti, GF, GS, V, P, S)
        $last[] = array($t,$v[$N][2],$v[$N][0],$v[$N][1],$v[$N][3],$v[$N][4],$v[$N][5]);
}

//Vittorie,Pareggi,Sconfitte,GF,GS Max e Min (Squadra | Valore)
$v_max = array($last[0][0], $last[0][4]);
$v_min = array($last[0][0], $last[0][4]);
$p_max = array($last[0][0], $last[0][5]);
$p_min = array($last[0][0], $last[0][5]);
$s_max = array($last[0][0], $last[0][6]);
$s_min = array($last[0][0], $last[0][6]);
$gf_max = array($last[0][0], $last[0][2]);
$gf_min = array($last[0][0], $last[0][2]);
$gs_max = array($last[0][0], $last[0][3]);
$gs_min = array($last[0][0], $last[0][3]);
for($i=1; $i<count($last); $i++) {
    if($last[$i][4]>$v_max[1]) $v_max=array($last[$i][0], $last[$i][4]);
    if($last[$i][4]<$v_min[1]) $v_min=array($last[$i][0], $last[$i][4]);
    if($last[$i][5]>$p_max[1]) $p_max=array($last[$i][0], $last[$i][5]);
    if($last[$i][5]<$p_min[1]) $p_min=array($last[$i][0], $last[$i][5]);
    if($last[$i][6]>$s_max[1]) $s_max=array($last[$i][0], $last[$i][6]);
    if($last[$i][6]<$s_min[1]) $s_min=array($last[$i][0], $last[$i][6]);
    if($last[$i][2]>$gf_max[1]) $gf_max=array($last[$i][0], $last[$i][2]);
    if($last[$i][2]<$gf_min[1]) $gf_min=array($last[$i][0], $last[$i][2]);
    if($last[$i][3]>$gs_max[1]) $gs_max=array($last[$i][0], $last[$i][3]);
    if($last[$i][3]<$gs_min[1]) $gs_min=array($last[$i][0], $last[$i][3]);
}
// Tot punti, max e min punti giornata, vittorie e sconfitte x 1-0
$points_min = array("", 0, 1000);
$points_max = array("", 0, 0);
if($champ) {
    foreach($champ as $sq=>$v) {
        $v10 = 0;
        $s10 = 0;
        foreach($v as $gg=>$vv) {
            if($vv[0] > $gf[2]) $gf = array($sq, $gg, $vv[0]);
            if($vv[7] > $points_max[2]) $points_max = array($sq, $gg, $vv[7]);
            if($vv[7] < $points_min[2]) $points_min = array($sq, $gg, $vv[7]);
            if($vv[0]==1 && $vv[1]==0) $v10++;
            if($vv[0]==0 && $vv[1]==1) $s10++;
            $tot_punti[$sq] = $tot_punti[$sq]+$vv[7];
        }
        if($v10 > $vitt_10[1]) $vitt_10 = array($sq, $v10);
        if($s10 > $sco_10[1]) $sco_10 = array($sq, $s10);
    }

    // Rapporto punti/gol fatti e ordinamento
    for($i=0; $i<count($last); $i++) {
        $pongol[] = array($last[$i][0], $last[$i][2]==0 ? 0 : $last[$i][1]/$last[$i][2]);
    }
    for($i=0; $i<count($pongol)-1; $i++) {
        $max = $pongol[$i][1];
        $imax = $i;
        for($j=$i+1; $j<count($pongol); $j++) {
            if($pongol[$j][1] > $max) {
                $max = $pongol[$j][1];
                $imax = $j;
            }
        }
        $pongol[$i][1] = round($pongol[$i][1],2);
        $pongol[$imax][1] = round($pongol[$imax][1],2);
        $temp = $pongol[$i];
        $pongol[$i] = $pongol[$imax];
        $pongol[$imax] = $temp;
    }
    // Vittorie, ris.utili, sconfitte consecutive
    foreach($champ as $sq=>$v) {
        $vitt = 0;
        $util = 0;
        $sco = 0;
        foreach($v as $gg=>$vv) {
            if($vv[6]=='V') {
                $vitt++;
                if($vitt > $v_cons[1]) $v_cons = array($sq, $vitt);
            }
            if($vv[6]!='V') $vitt=0;
            if($vv[6]=='V' || $vv[6]=='P') {
                $util++;
                if($util > $u_cons[1]) $u_cons = array($sq, $util);
            }
            if($vv[6]=='S') $util=0;
            if($vv[6]=='S') {
                $sco++;
                if($sco > $s_cons[1]) $s_cons = array($sq, $sco);
            }
            if($vv[6]!='S') $sco=0;
        }
    }
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
        <? @include("page_elements/scripts.php"); ?>
        <script type="text/javascript">

        $(document).ready(function() {

            $('.top').mouseover(function() {
                var cells = $(this).parent().children();
                for(var i=0; i<4; i++)
                    $(cells[i]).css("background-color", "#95E873");
            });
            $('.top').mouseout(function() {
                var cells = $(this).parent().children();
                for(var i=0; i<4; i++)
                    $(cells[i]).css("background-color", "#C9FEBF");
            });

            $('.flop').mouseover(function() {
                var cells = $(this).parent().children();
                for(var i=4; i<8; i++)
                    $(cells[i]).css("background-color", "#E84A6D");
            });
            $('.flop').mouseout(function() {
                var cells = $(this).parent().children();
                for(var i=4; i<8; i++)
                    $(cells[i]).css("background-color", "#FEC3BF");
            });

        });

        </script>
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
                <h2>Statistiche Fantacalcio</h2>
                <table align="center" class="stat">
                    <tr class="title">
                        <td colspan="2">Vittorie</td><td colspan="2">Pareggi</td><td colspan="2">Sconfitte</td>
                    </tr>
                    <tr>
                        <td class="max"><a href="stat_squadra.php?team=<?echo $v_max[0]?>"><?echo $v_max[0]?></a></td><td class="data"><?echo $v_max[1]?></td>
                        <td class="max"><a href="stat_squadra.php?team=<?echo $p_max[0]?>"><?echo $p_max[0]?></a></td><td class="data"><?echo $p_max[1]?></td>
                        <td class="max"><a href="stat_squadra.php?team=<?echo $s_max[0]?>"><?echo $s_max[0]?></a></td><td class="data"><?echo $s_max[1]?></td>
                    </tr>
                    <tr>
                        <td class="min"><a href="stat_squadra.php?team=<?echo $v_min[0]?>"><?echo $v_min[0]?></a></td><td class="data"><?echo $v_min[1]?></td>
                        <td class="min"><a href="stat_squadra.php?team=<?echo $p_min[0]?>"><?echo $p_min[0]?></a></td><td class="data"><?echo $p_min[1]?></td>
                        <td class="min"><a href="stat_squadra.php?team=<?echo $s_min[0]?>"><?echo $s_min[0]?></a></td><td class="data"><?echo $s_min[1]?></td>
                    </tr>
                    <tr><td colspan="8"><br/></td></tr>
                    <tr class="title">
                        <td colspan="3">Gol fatti</td><td colspan="3">Gol subiti</td>
                    </tr>
                    <tr>
                        <td class="max" colspan="2"><a href="stat_squadra.php?team=<?echo $gf_max[0]?>"><?echo $gf_max[0]?></a></td><td class="data"><?echo $gf_max[1]?></td>
                        <td class="max" colspan="2"><a href="stat_squadra.php?team=<?echo $gs_min[0]?>"><?echo $gs_min[0]?></a></td><td class="data"><?echo $gs_min[1]?></td>
                    </tr>
                    <tr>
                        <td class="min" colspan="2"><a href="stat_squadra.php?team=<?echo $gf_min[0]?>"><?echo $gf_min[0]?></a></td><td class="data"><?echo $gf_min[1]?></td>
                        <td class="min" colspan="2"><a href="stat_squadra.php?team=<?echo $gs_max[0]?>"><?echo $gs_max[0]?></a></td><td class="data"><?echo $gs_max[1]?></td>
                    </tr>
                    <tr><td colspan="8"><br/></td></tr>
                    <tr class="title">
                        <td colspan="2">Vittorie cons.</td><td colspan="2">Ris. utili cons.</td><td colspan="2">Sconfitte cons.</td>
                    </tr>
                    <tr>
                        <td class="team"><a href="stat_squadra.php?team=<?echo $v_cons[0]?>"><?echo $v_cons[0]?></a></td><td class="data"><?echo $v_cons[1]?></td>
                        <td class="team"><a href="stat_squadra.php?team=<?echo $u_cons[0]?>"><?echo $u_cons[0]?></a></td><td class="data"><?echo $u_cons[1]?></td>
                        <td class="team"><a href="stat_squadra.php?team=<?echo $s_cons[0]?>"><?echo $s_cons[0]?></a></td><td class="data"><?echo $s_cons[1]?></td>
                    </tr>
                    <tr><td><br/></td></tr>
                    <tr class="title">
                        <td colspan="2">Vittorie 1-0</td><td colspan="2">Sconfitte 1-0</td><td colspan="2">Punteggi Max / Min</td>
                    </tr>
                    <tr>
                        <td class="team"><a href="stat_squadra.php?team=<?echo $vitt_10[0]?>"><?echo $vitt_10[0]?></a></td><td class="data"><?echo $vitt_10[1]?></td>
                        <td class="team"><a href="stat_squadra.php?team=<?echo $sco_10[0]?>"><?echo $sco_10[0]?></a></td><td class="data"><?echo $sco_10[1]?></td>
                        <td class="max"><a href="stat_squadra.php?team=<?echo $points_max[0]?>"><?echo $points_max[0]?></a></td><td class="data"><?echo $points_max[2]?></td>
                    </tr>
                    <tr>
                        <td></td><td></td>
                        <td></td><td></td>
                        <td class="min"><a href="stat_squadra.php?team=<?echo $points_min[0]?>"><?echo $points_min[0]?></a></td><td class="data"><?echo $points_min[2]?></td>
                    </tr>
                    <tr><td colspan="8"><br/></td></tr>
                    <tr class="title">
                        <td colspan="3">Punti totali</td><td colspan="3">Rapporto Punti/Gol fatti</td>
                    </tr>
                    <? for($i=0; $i<count($pongol); $i++) { ?>
                    <tr>
                        <td class="team" colspan="2"><a href="stat_squadra.php?team=<?echo $pongol[$i][0]?>"><?echo $pongol[$i][0]?></a></td><td class="data"><?echo $tot_punti[$pongol[$i][0]]?></td>
                        <td class="team" colspan="2"><a href="stat_squadra.php?team=<?echo $pongol[$i][0]?>"><?echo $pongol[$i][0]?></a></td><td class="data"><?echo $pongol[$i][1]?></td>
                    </tr>
                    <? } ?>
                </table>
                <br/><br/>
                <h2>Statistiche Serie A</h2>
                <table class="stat" align="center" style="margin-bottom: 15px;">
                    <tr>
                        <td align="center" colspan="8"><h4>Top &amp; Flop</h4></td>
                    </tr>

                    <tr class="title">
                        <td align="center" colspan="8">Portieri</td>
                    </tr>
                    <tr class="header">
                        <td></td>
                        <td>Nome</td>
                        <td>Media</td>
                        <td>Media con B/M</td>
                        <td></td>
                        <td>Nome</td>
                        <td>Media</td>
                        <td>Media con B/M</td>
                    </tr>
                    <? for($i=0; $i < mysql_num_rows($top_p); $i++) {
                        $tp = mysql_fetch_row($top_p);
                        $fp = mysql_fetch_row($flop_p); ?>
                    <tr>
                        <td class="top"><img src="<? echo "teams/".$tp[1].".gif" ?>" alt="<? echo $tp[1] ?>" title="<? echo $tp[1] ?>"/></td>
                        <td class="top"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($tp[2])?>"><?echo $tp[2]?></a></td>
                        <td align="center" class="top"><?echo round($tp[3],2)?></td>
                        <td align="center" class="top"><?echo round($tp[4],2)?></td>
                        <td class="flop"><img src="<? echo "teams/".$fp[1].".gif" ?>" alt="<? echo $fp[1] ?>" title="<? echo $fp[1] ?>"/></td>
                        <td class="flop"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($fp[2])?>"><?echo $fp[2]?></a></td>
                        <td align="center" class="flop"><?echo round($fp[3],2)?></td>
                        <td align="center" class="flop"><?echo round($fp[4],2)?></td>
                    </tr>
                    <? } ?>
                    <tr><td><br/></td></tr>
                    <tr class="title">
                        <td align="center" colspan="8">Difensori</td>
                    </tr>
                    <tr class="header">
                        <td></td>
                        <td>Nome</td>
                        <td>Media</td>
                        <td>Media con B/M</td><td></td>
                        <td>Nome</td>
                        <td>Media</td>
                        <td>Media con B/M</td>
                    </tr>
                    <? for($i=0; $i < mysql_num_rows($top_d); $i++) {
                        $td = mysql_fetch_row($top_d);
                        $fd = mysql_fetch_row($flop_d); ?>
                    <tr>
                        <td class="top"><img src="<? echo "teams/".$td[1].".gif" ?>" alt="<? echo $td[1] ?>" title="<? echo $td[1] ?>"/></td>
                        <td class="top"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($td[2])?>"><?echo $td[2]?></a></td>
                        <td align="center" class="top"><?echo round($td[3],2)?></td>
                        <td align="center" class="top"><?echo round($td[4],2)?></td>
                        <td class="flop"><img src="<? echo "teams/".$fd[1].".gif" ?>" alt="<? echo $fd[1] ?>" title="<? echo $fd[1] ?>"/></td>
                        <td class="flop"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($fd[2])?>"><?echo $fd[2]?></a></td>
                        <td align="center" class="flop"><?echo round($fd[3],2)?></td>
                        <td align="center" class="flop"><?echo round($fd[4],2)?></td>
                    </tr>
                    <? } ?>
                    <tr><td><br/></td></tr>
                    <tr class="title">
                        <td align="center" colspan="8">Centrocampisti</td>
                    </tr>
                    <tr class="header">
                        <td></td>
                        <td>Nome</td>
                        <td>Media</td>
                        <td>Media con B/M</td><td></td>
                        <td>Nome</td>
                        <td>Media</td>
                        <td>Media con B/M</td>
                    </tr>
                    <? for($i=0; $i < mysql_num_rows($top_c); $i++) {
                        $tc = mysql_fetch_row($top_c);
                        $fc = mysql_fetch_row($flop_c); ?>
                    <tr>
                        <td class="top"><img src="<? echo "teams/".$tc[1].".gif" ?>" alt="<? echo $tc[1] ?>" title="<? echo $tc[1] ?>"/></td>
                        <td class="top"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($tc[2])?>"><?echo $tc[2]?></a></td>
                        <td align="center" class="top"><?echo round($tc[3],2)?></td>
                        <td align="center" class="top"><?echo round($tc[4],2)?></td>
                        <td class="flop"><img src="<? echo "teams/".$fc[1].".gif" ?>" alt="<? echo $fc[1] ?>" title="<? echo $fc[1] ?>"/></td>
                        <td class="flop"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($fc[2])?>"><?echo $fc[2]?></a></td>
                        <td align="center" class="flop"><?echo round($fc[3],2)?></td>
                        <td align="center" class="flop"><?echo round($fc[4],2)?></td>
                    </tr>
                    <? } ?>
                    <tr><td><br/></td></tr>
                    <tr class="title">
                        <td align="center" colspan="8">Attaccanti</td>
                    </tr>
                    <tr class="header">
                        <td></td>
                        <td>Nome</td>
                        <td>Media</td>
                        <td>Media con B/M</td><td></td>
                        <td>Nome</td>
                        <td>Media</td>
                        <td>Media con B/M</td>
                    </tr>
                    <? for($i=0; $i < mysql_num_rows($top_a); $i++) {
                        $ta = mysql_fetch_row($top_a);
                        $fa = mysql_fetch_row($flop_a); ?>
                    <tr>
                        <td class="top"><img src="<? echo "teams/".$ta[1].".gif" ?>" alt="<? echo $ta[1] ?>" title="<? echo $ta[1] ?>"/></td>
                        <td class="top"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($ta[2])?>"><?echo $ta[2]?></a></td>
                        <td align="center" class="top"><?echo round($ta[3],2)?></td>
                        <td align="center" class="top"><?echo round($ta[4],2)?></td>
                        <td class="flop"><img src="<? echo "teams/".$fa[1].".gif" ?>" alt="<? echo $fa[1] ?>" title="<? echo $fa[1] ?>"/></td>
                        <td class="flop"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($fa[2])?>"><?echo $fa[2]?></a></td>
                        <td align="center" class="flop"><?echo round($fa[3],2)?></td>
                        <td align="center" class="flop"><?echo round($fa[4],2)?></td>
                    </tr>
                    <? } ?>
                    <tr><td><br/></td></tr>
                    <tr class="title">
                        <td align="center" colspan="4"><h4>Classifica Marcatori</h4></td>
                        <td align="center" colspan="4"><h4>Classifica Assist</h4></td>
                    </tr>
                    <? for($i=0; $i<mysql_num_rows($goleador); $i++) {
                        $g = mysql_fetch_row($goleador);
                        $a = mysql_fetch_row($assist); ?>
                    <tr>
                        <td><img src="<? echo "teams/".$g[1].".gif" ?>" alt="<? echo $g[1] ?>" title="<? echo $g[1] ?>"/></td>
                        <td class="name" colspan="2"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($g[2])?>"><?echo $g[2]?></a></td>
                        <td class="data"><?echo $g[3]?></td>
                        <td><img src="<? echo "teams/".$a[1].".gif" ?>" alt="<? echo $a[1] ?>" title="<? echo $a[1] ?>"/></td>
                        <td class="name" colspan="2"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($a[2])?>"><?echo $a[2]?></a></td>
                        <td class="data"><?echo $a[3]?></td>
                    </tr>
                    <? } ?>
                    <tr><td><br/></td></tr>
                    <tr class="title">
                        <td align="center" colspan="4"><h4>I pi&ugrave; ammoniti</h4></td>
                        <td align="center" colspan="4"><h4>I pi&ugrave; espulsi</h4></td>
                    </tr>
                    <? for($i=0; $i<mysql_num_rows($ammoniti); $i++) {
                        $a = mysql_fetch_row($ammoniti);
                        $e = mysql_fetch_row($espulsi); ?>
                    <tr>
                        <td><img src="<? echo "teams/".$a[1].".gif" ?>" alt="<? echo $a[1] ?>" title="<? echo $a[1] ?>"/></td>
                        <td class="name" colspan="2"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($a[2])?>"><?echo $a[2]?></a></td>
                        <td class="data"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($a[2])?>"><?echo $a[3]?></a></td>
                        <td><img src="<? echo "teams/".$e[1].".gif" ?>" alt="<? echo $e[1] ?>" title="<? echo $e[1] ?>"/></td>
                        <td class="name" colspan="2"><a class="linkrsgio" title="Click x statistiche" href="stat_giocatore.php?n=<?echo urlencode($e[2])?>"><?echo $e[2]?></a></td>
                        <td class="data"><?echo $e[3]?></td>
                    </tr>
                    <? } ?>
                </table>
            </div>
        </div>
        <div id="footer">
            <p>Copyright (c) 2009 <a href="http://www.andreamartelli.it">Andrea Martelli</a>. All rights reserved.</p>
        </div>
    </body>
</html>