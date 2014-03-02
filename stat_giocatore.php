<?php

include_once dirname(__FILE__) . "/engine/engine.php";

$nome = $_GET['n'];
if(!$nome) system_error("Errore: Il nome del giocatore richiesto è nullo o non valido");
$result = mysql_query("SELECT seriea,squadra,ruolo,giornata,voto,bonusmalus,gs,gf,autogol,gdv,gdp,ammonizioni,espulsioni,assist,rigsba,rigpar,titolare FROM voti AS v JOIN giocatori AS g ON v.nome=g.nome WHERE v.nome='$nome'");
$N = mysql_num_rows($result);
if($N==0) {
    $result = mysql_query("SELECT seriea,squadra,ruolo FROM giocatori WHERE nome='$nome'");
    $r = mysql_fetch_row($result);
    $seriea = $r[0];
    $team = $r[1];
    $ruolo = $r[2];
}
close_db();
while($r = mysql_fetch_row($result)) {
    $seriea = $r[0];
    $team = $r[1];
    $ruolo = $r[2];
    $voti[] = array($r[3], $r[4]);        //2 serie dati x il grafico
    $votibm[] = array($r[3], $r[4]+$r[5]);
    for($i=0; $i<13; $i++)
    $tot[$i] = $tot[$i] + $r[$i+4];
}
$tot[1] = $N==0 ? 0 : ($tot[0] + $tot[1])/$N;   //medie voto e voto+bm
$tot[0] = $N==0 ? 0 : $tot[0]/$N;
if(mysql_num_rows($result)) mysql_data_seek($result, 0);
if($ruolo=="P") $col = "#2AAAFF";
if($ruolo=="D") $col = "#2AFF2A";
if($ruolo=="C") $col = "#FFFFAA";
if($ruolo=="A") $col = "#FF5555";

if($voti && $votibm)
{
    $data = array();
    for($i = 0; $i < count($voti); $i++)
    {
        $data[] = array('y' => strval($voti[$i][0]),
                        'vbm' => $votibm[$i][1],
                        'v' => $voti[$i][1]);
    }
    
    $options['element'] = 'andamento';
    $options['data'] = $data;
    $options['xkey'] = 'y';
    $options['ykeys'] = array('vbm', 'v');
    $options['labels'] = array('Voto+BM', 'Voto');
    $options['lineColors'] = array('#FF2A2A', '#5BFF3A');
    $options['hideHover'] = true;
    $options['behaveLikeLine'] = true;
    $options['fillOpacity'] = 0.4;

    $morrisjs_config = json_encode($options);
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
        <style type="text/css">
            .line:hover {
                background-color: #ABFF79;
            }
        </style>
         <script type="text/javascript">
            $(document).ready(function() {                
                // Creazione grafico con Morris.js
                Morris.Area(JSON.parse('<?= $morrisjs_config ?>'));
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
                <h2><? echo $nome; ?></h2>
                <!-- Info generali -->
                <table>
                    <tr>
                        <td width="256px"><a class="fotosq" href="serieateam.php?t=<?echo $seriea?>"><img width="128px" height="128px" src="<? echo "teams/big/".$seriea.".png" ?>" alt="<? echo $seriea ?>" title="<? echo $seriea ?>"/></a></td>
                        <td>
                            <table>
                                <tr>
                                    <td style="font-size: 14px; font-weight: bold; color: #2582A4;">Squadra: </td><td style="font-weight: bold; font-size: 16px;"><?= $team?></td>
                                </tr>
                                <tr>
                                    <td style="font-size: 14px; font-weight: bold; color: #2582A4;">Ruolo: </td><td style="font-weight: bold; font-size: 16px; color: <?echo $col;?>"><?= $ruolo?></td>
                                </tr>
                                <tr>
                                    <td style="font-size: 14px; font-weight: bold; color: #2582A4;">Voto Medio: </td><td style="font-weight: bold; font-size: 20px; color: #0066FF;"><?= round($tot[0], 2) ?></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <br/>
                <!-- Grafico andamento -->
                <div id="andamento" style="width: 617px; height: 300px;"></div>
                
                <br/><br/>
                <!-- Totale e dettaglio giornate -->
                <table align="center" class="stat" style="width: 100%;">
                    <tr class="title" align="center"><td colspan="14">Totali</td></tr>
                    <tr class="header">
                        <td></td>
                        <td>Voto</td>
                        <td>B/M</td>
                        <td>GS</td>
                        <td>GF</td>
                        <td>Aut.</td>
                        <td>GdV</td>
                        <td>GdP</td>
                        <td>A</td>
                        <td>E</td>
                        <td>Ass.</td>
                        <td>RigSba</td>
                        <td>RigPar</td>
                        <td>T</td>
                    </tr>
                    <tr style="background-color: #91DBF3;">
                        <td></td>
                        <? for($i=0; $i<count($tot); $i++) { ?>
                        <td class="data"><?echo is_int($tot[$i]) ? $tot[$i] : round($tot[$i],2) ?></td>
                        <? } ?>
                    </tr>
                    <tr><td><br/></td></tr>
                    <tr class="title" align="center"><td colspan="14">Dettaglio giornate</td></tr>
                    <tr class="header">
                        <td>G</td>
                        <td>Voto</td>
                        <td>B/M</td>
                        <td>GS</td>
                        <td>GF</td>
                        <td>Aut.</td>
                        <td>GdV</td>
                        <td>GdP</td>
                        <td>A</td>
                        <td>E</td>
                        <td>Ass.</td>
                        <td>RigSba</td>
                        <td>RigPar</td>
                        <td>T</td>
                    </tr>
                    <? while($r = mysql_fetch_row($result)) { ?>
                    <tr class="line">
                        <? for($i=3; $i<count($r); $i++) {
                            if($i==3) { ?>
                        <td class="data"><a title="Click x statistiche" href="archivio_formazioni.php?n=<?echo $r[$i]?>"><?echo $r[$i]?></a></td>
                        <? } else { ?>
                        <td class="data"><?echo $r[$i]?></td>
                        <? }?>
                    <? } ?>
                    </tr>
                  <? } ?>
                </table>
                <br/><br/><br/>
            </div>
        </div>
        <div id="footer">
            <p>Copyright (c) 2009 <a href="http://www.andreamartelli.it">Andrea Martelli</a>. All rights reserved.</p>
        </div>
    </body>
</html>