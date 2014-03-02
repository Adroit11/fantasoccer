<?php

include_once dirname(__FILE__) . "/engine/engine.php";

$ok = mysql_query("SELECT * FROM squadre WHERE nome='".$_GET['team']."'");
if(!$_GET['team'] || $_GET['team']==null || $_GET['team']=="" || !$ok || mysql_num_rows($ok)==0)
    system_error("Impossibile selezionare la squadra");
    
$team = $_GET['team'];

$result = mysql_query("SELECT giornata,squadra1,squadra2,punti1,punti2,gol1,gol2,risultato1,risultato2,torneo FROM calendario AS c JOIN giornate AS g ON c.giornata=g.n WHERE punti1 IS NOT NULL AND punti2 IS NOT NULL AND (squadra1='$team' OR squadra2='$team') ORDER BY giornata", $mysql);
$top = mysql_query("SELECT DISTINCT g.seriea,v.nome,AVG(voto) AS avg_voto,AVG(voto+bonusmalus) FROM voti AS v JOIN giocatori AS g ON v.nome=g.nome WHERE squadra='$team' GROUP BY nome ORDER BY avg_voto DESC LIMIT 0,5");
$flop = mysql_query("SELECT DISTINCT g.seriea,v.nome,AVG(voto) AS avg_voto,AVG(voto+bonusmalus) FROM voti AS v JOIN giocatori AS g ON v.nome=g.nome WHERE squadra='$team' GROUP BY nome ORDER BY avg_voto ASC LIMIT 0,5");
$goleador = mysql_query("SELECT DISTINCT g.seriea,v.nome,SUM(gf) AS sum_gf FROM voti AS v JOIN giocatori AS g ON v.nome=g.nome WHERE squadra='$team' GROUP BY nome ORDER BY sum_gf DESC LIMIT 0,3");
$assist = mysql_query("SELECT DISTINCT g.seriea,v.nome,SUM(assist) AS sum_ass FROM voti AS v JOIN giocatori AS g ON v.nome=g.nome WHERE squadra='$team' GROUP BY nome ORDER BY sum_ass DESC LIMIT 0,3");
$ammoniti = mysql_query("SELECT DISTINCT g.seriea,v.nome,SUM(ammonizioni) AS sum_amm FROM voti AS v JOIN giocatori AS g ON v.nome=g.nome WHERE squadra='$team' GROUP BY nome ORDER BY sum_amm DESC LIMIT 0,3");
$espulsi = mysql_query("SELECT DISTINCT g.seriea,v.nome,SUM(espulsioni) AS sum_esp FROM voti AS v JOIN giocatori AS g ON v.nome=g.nome WHERE squadra='$team' GROUP BY nome ORDER BY sum_esp DESC LIMIT 0,3");

if(!$result || !$top || !$flop || !$goleador || !$assist || !$ammoniti || !$espulsi)
    system_error("Errore nell'ottenimento delle statistiche della squadra");

$count = 0;
$prev = 0;
$num_teams = count(get_team_names());

close_db();

while($r = mysql_fetch_row($result)) {
    if($r[9]==false) {            // Solo campionato
        if($r[1]==$team) $o=0;
        else if($r[2]==$team) $o=1;
        $v1=0; $v2=0; $pa1=0; $pa2=0; $s1=0; $s2=0;
        if($r[5+$o]>$r[6-$o]) {
            $p1 = 3; $v1=1;
            $p2 = 0; $s2=1;
        } else if($r[6-$o]>$r[5+$o]) {
            $p1 = 0; $s1=1;
            $p2 = 3; $v2=1;
        } else {
            $p1 = 1; $pa1=1;
            $p2 = 1; $pa2=1;
        }
        // [Giornata]--> GF, GS, P, V, P, S, Ris
        $champ[$r[0]] = array($r[5+$o]+$champ[$prev][0], $r[6-$o]+$champ[$prev][1],
            $p1+$champ[$prev][2],$v1+$champ[$prev][3],$pa1+$champ[$prev][4],$s1+$champ[$prev][5],$r[7+$o]);

        if($r[0] != $prev)
            $prev = $r[0];
    }
}
if(isset($champ))
{
    mysql_data_seek($result, 0);
    
    // Situazione finale classifica della squadra
    $N = max(array_keys($champ));
    $last = array($champ[$N][2],$champ[$N][0],$champ[$N][1],$champ[$N][3],$champ[$N][4],$champ[$N][5]);
}

/*
 * Grafico risultati squadra
 */
if(isset($last)) {

    $tot = $last[3]+$last[4]+$last[5];
    $v = $last[3];
    $p = $last[4];
    $s = $last[5];
    $options['element'] = "donut";
    $options['data'] = array(
        array('label' => 'V', 'value' => $v),
        array('label' => 'P', 'value' => $p),
        array('label' => 'S', 'value' => $s)
    );
    $options['colors'] = array('#5BFF3A', '#FBF583', '#FF2A2A');
    $morrisjs_config = json_encode($options);
}

$lockroles = isset($_GET['lockroles']) ? ($_GET['lockroles'] == 'true') : true;
$sortfield = isset($_GET['sortfield']) ? $_GET['sortfield'] : 'mv';
$sortorder = isset($_GET['sortorder']) ? $_GET['sortorder'] : 'DESC';
$table = get_team_players_data($team, $lockroles, $sortfield, $sortorder);

function formatTableHeader($field)
{
    global $sortfield, $sortorder;
    if($sortfield == $field) 
        echo ' order="' . $sortorder . '"';
}

function formatTableHeaderArrow($field)
{
    global $sortfield, $sortorder;
    if($sortfield == $field)
        echo '<img src="images/' . ($sortorder == 'DESC' ? 'down' : 'up') . '.png"/>';
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
        <? @include("page_elements/scripts_updated.php"); ?>
        <link href="<?= $baseUrl ?>javascript/morris/morris.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="<?= $baseUrl ?>javascript/raphael-min.js"></script>
        <script type="text/javascript" src="<?= $baseUrl ?>javascript/morris/morris.min.js"></script>
        
        <link rel="shortcut icon" href="pics/favicon.ico"/>
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
            
            // Gestione ordinamenti tabella
            $('#allstats .header td').click(function() {
                var fieldName = $(this).attr('class');
                
                if(fieldName === 'ruolo')
                    return;
                
                var fieldOrder = $(this).attr('order');
                var newOrder = 'DESC';
                if(typeof fieldOrder !== 'undefined' && fieldOrder !== false)
                {
                    // Il campo era precedentemente selezionato. Invertire l'ordine
                    newOrder = (fieldOrder === 'ASC') ? 'DESC' : 'ASC';
                }
                var lockRoles = $('#lockroles').is(':checked') ? 'true' : 'false';
                window.location = '<?= $baseUrl ?>stat_squadra.php?team=<?= $team ?>&sortfield=' + fieldName + '&sortorder=' + newOrder + '&lockroles=' + lockRoles;
            });

            Morris.Donut(JSON.parse('<?= $morrisjs_config ?>'));
        });

        </script>
        <style type="text/css">
            .header {
                height: 30px;
            }
            #allstats td {
                padding-left: 8px;
                padding-right: 8px;
            }
            #allstats .header td {
                cursor: pointer;
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
                <h2><?echo $team?></h2>
                <h3>Situazione campionato</h3>
                <table align="center">
                    <tr>
                        <td>
                            <table id="classrow" class="classifica" style="width: 350px;">
                                <thead>
                                    <th>Punti</th>
                                    <th>GF</th>
                                    <th>GS</th>
                                    <th>Vittorie</th>
                                    <th>Pareggi</th>
                                    <th>Sconfitte</th>
                                    <th>Giocate</th>
                                </thead>
                                <tr>
                                    <? for($i=0; $i<count($last); $i++) { ?>
                                    <td class="data"><?echo $last[$i]?></td>
                                    <? } ?>
                                    <td class="data"><?echo $last[3]+$last[4]+$last[5]?></td>
                                </tr>
                            </table>
                        </td>
                        <td>
                            <div id="donut" style="width: 150px; height: 150px; margin-left: 100px;"></div>
                        </td>
                    </tr>
                </table>
                <br/>
                
                <h3>Statistiche giocatori</h3><br/>
                <!-- Tabella con statistiche per ogni giocatore -->
                <table align="center" class="stat" id="allstats">
                    <tr class="header">
                        <td colspan="2" class="nome" <? formatTableHeader('nome') ?>>Giocatore<? formatTableHeaderArrow('nome') ?></td>
                        <td class="ruolo"<? formatTableHeader('ruolo') ?>>
                            Ruolo<? formatTableHeaderArrow('ruolo') ?>
                            <input type="checkbox" name="lockroles" id="lockroles" <? if($lockroles) echo ' checked="checked"'; ?>/>
                        </td>
                        <td class="mv" <? formatTableHeader('mv') ?>>MV<? formatTableHeaderArrow('mv') ?></td>
                        <td class="mvbm" <? formatTableHeader('mvbm') ?>>MV+BM<? formatTableHeaderArrow('mvbm') ?></td>
                        <td class="gf" <? formatTableHeader('gf') ?>>Gf<? formatTableHeaderArrow('gf') ?></td>
                        <td class="gs" <? formatTableHeader('gs') ?>>Gs<? formatTableHeaderArrow('gs') ?></td>
                        <td class="ass" <? formatTableHeader('ass') ?>>Ass<? formatTableHeaderArrow('ass') ?></td>
                        <td class="auto" <? formatTableHeader('auto') ?>>Agol<? formatTableHeaderArrow('auto') ?></td>
                        <td class="amm" <? formatTableHeader('amm') ?>>Amm<? formatTableHeaderArrow('amm') ?></td>
                        <td class="esp" <? formatTableHeader('esp') ?>>Esp<? formatTableHeaderArrow('esp') ?></td>
                        <td class="rpar" <? formatTableHeader('rpar') ?>>Rpar<? formatTableHeaderArrow('rpar') ?></td>
                        <td class="rsba" <? formatTableHeader('rsba') ?>>Rsba<? formatTableHeaderArrow('rsba') ?></td>
                        <td class="t" <? formatTableHeader('t') ?>>T<? formatTableHeaderArrow('t') ?></td>
                        <td class="p" <? formatTableHeader('p') ?>>P<? formatTableHeaderArrow('p') ?></td>
                    </tr>
                    <?
                    if($table == null or empty($table) || count($table) == 0)
                    {
                    ?>
                       <tr><td colspan="15">Non sono ancora disponibili statistiche su nessun giocatore</td></tr>  
                    <?
                    }
                    else
                    foreach($table as $t)
                    {
                    ?>
                    <tr class="<?= $t['ruolo'] ?>" align="center">
                        <td>
                            <a class="fotosq" href="<?= $baseUrl."serieateam.php?t=".$t['seriea'] ?>">
                                <img src="<?= $baseUrl."teams/".$t['seriea'].".gif" ?>" alt="<?= $t['seriea'] ?>" title="<?= $t['seriea'] ?>"/>
                            </a>
                        </td>
                        <td>
                            <a class="linkrsgio" href="<?= $baseUrl.'stat_giocatore.php?n='.$t['nome'] ?>"><?= $t['nome'] ?></a>
                        </td>
                        <td><?= $t['ruolo'] ?></td>
                        <td style="font-weight: bold; color: #054BB3;"><?= $t['mv'] ?></td>
                        <td style="font-weight: bold; font-style: italic;"><?= $t['mvbm'] ?></td>
                        <td><?= $t['gf'] ?></td>
                        <td><?= $t['gs'] ?></td>
                        <td><?= $t['ass'] ?></td>
                        <td><?= $t['auto'] ?></td>
                        <td><?= $t['amm'] ?></td>
                        <td><?= $t['esp'] ?></td>
                        <td><?= $t['rpar'] ?></td>
                        <td><?= $t['rsba'] ?></td>
                        <td><?= $t['t'] ?></td>
                        <td><?= $t['p'] ?></td>
                    </tr>
                    <?
                    }
                    ?>
                </table>
                
                
                <h3>Risultati partite</h3>
                <table align="center" class="stat"><!-- Risultati partite -->

                    <tr class="header">
                        <td>G</td><td></td><td></td><td colspan="2">Punti</td><td colspan="2">Gol</td>
                    </tr>
                    <? while($r = mysql_fetch_row($result)) { 
                        if($r[9]==false) { ?>
                        <tr style="height: 25px; background-color: #F4F9F9;">
                            <td><?echo $r[0]?></td>
                            <? if($r[1]==$team) $style1='style="color: #011E66; font-weight: bold;"'; ?>
                            <? if($r[2]==$team) $style2='style="color: #011E66; font-weight: bold;"'; ?>
                            <td <?echo $style1 ?> width="80px"><?echo $r[1]?></td>
                            <td <?echo $style2 ?> width="80px"><?echo $r[2]?></td>
                            <td style="font-style: italic;" align="center" width="30px"><?echo $r[3]?></td>
                            <td style="font-style: italic;" align="center" width="30px"><?echo $r[4]?></td>
                            <td <?echo $style1 ?> align="center" width="30px"><?echo $r[5]?></td>
                            <td <?echo $style2 ?> align="center" width="30px"><?echo $r[6]?></td>
                        </tr>
                    <? }
                    } ?>
                </table>
                <br/>
            </div>
        </div>
        <div id="footer">
            <p>Copyright (c) 2009 <a href="http://www.andreamartelli.it">Andrea Martelli</a>. All rights reserved.</p>
        </div>
    </body>
</html>