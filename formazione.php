<?php

include_once dirname(__FILE__) . "/engine/engine.php";
//include_once( "engine/engine.php" );

if(empty($_SESSION['tipo']) || empty($_SESSION['username']) || $_SESSION['tipo']=='ADMIN') {

    access_denied();

} else {
    
    // Ottengo nome squadra
    $result = mysql_query("SELECT nome FROM squadre WHERE presidente='".$_SESSION['username']."'", $mysql);
    if(!$result) system_error("Errore: impossibile ottenere nome squadra");
    $result = mysql_fetch_row($result);
    $team = $result[0];
    
    // Ottengo rosa squadra
    $rosa = array();
    $all_players = array();
    $result = mysql_query("SELECT nome, seriea, ruolo FROM giocatori WHERE squadra='$team'", $mysql);
    if(!$result) system_error("Errore: Impossibile ottenere la rosa della squadra");
    while($r = mysql_fetch_row($result))
    {
        $rosa[$r[2]][] = array($r[1],$r[0]);
        // crea stringa JSON nascosta con la rosa da inviare per la verifica
        // dei giocatori titolari
        $all_players[] = array($r[0], $r[1], $r[2]);
    }
    
    // Ottengo numero della prossima giornata da giocare
    $result = mysql_query("SELECT MIN(n) FROM giornate WHERE giocata=FALSE", $mysql);
    if(!$result) system_error("Errore: impossibile determinare ultima giornata giocata");
    $result = mysql_fetch_row($result);
    $giornata = $result[0];

    // Verifico se il tempo limite per l'inserimento della formazioni è stato superato
    $time_expired = sportday_time_expired($giornata);
    
    // Verifico se la formazione è stata già inserita e nel caso prelevo l'ultima
    $result = mysql_query("SELECT MAX(orario) FROM formazioni_temp WHERE squadra='$team'", $mysql);
    $presente = false;
    $orario_last = null;
    if(!$result) system_error("Errore: impossibile prelevare ultima formazione inserita");
    $result = mysql_fetch_row($result);
    $orario_last = $result[0];
    if($orario_last) {
        $presente = true;
        $ALL = mysql_query("SELECT seriea, f.nome, ruolo, tipo FROM giocatori AS g JOIN formazioni_temp AS f ON g.nome=f.nome WHERE f.squadra='$team' AND f.orario='$orario_last'", $mysql);
        if(!$ALL) system_error("Errore: impossibile prelevare ultima formazione inserita");
        while($r = mysql_fetch_row($ALL))
            $form[$r[3]][$r[2]][] = array($r[0], $r[1]);
    }
    
    $default_dif = 3;
    $default_cen = 4;
    $default_att = 3;

    // Eventuale inserimento formazione (nella tabella temporanea)
    if($_POST['action']=='insert')
    {
        if(sportday_time_expired($giornata))
        {
            system_error("Errore: tempo scaduto per l'inserimento della formazione!");
            exit;
        }
        
        // Add n hours to compensate the time-zone offset
        $date = date('Y-m-d H:i:s', time() + (60 * 60 * $timeZoneOffset));
        
        $tit = $_POST['giocatori'];
        $pr = $_POST['prime_riserve'];
        $sr = $_POST['seconde_riserve'];

        $first = true;
        $query = 'INSERT INTO formazioni_temp(squadra, nome, tipo, orario) VALUES';
        for($i=0; $i < count($tit); $i++) {
            if (!$first) $query .= ', ';
            $query .= " ('$team','$tit[$i]','TITOLARE','$date')";
            $first = false;
        }
        for($i=0; $i < count($pr); $i++)
            $query .= ", ('$team','$pr[$i]','PRIMA_RISERVA','$date')";
        for($i=0; $i < count($sr); $i++)
            $query .= ", ('$team','$sr[$i]','SECONDA_RISERVA','$date')";
        $newf = mysql_query($query, $mysql);

        // Inserimento evento nello stream
        if($presente) {
            // evento modifica formazione
            stream_event_put('FORM_MOD', $giornata, $_SESSION['username']);
        } else {
            // evento inserimento formazione
            stream_event_put('FORM_INS', $giornata, $_SESSION['username']);
        }

        if(!$newf) system_error("Errore: impossibile inserire la formazione!");
        header("location: form_giornata.php");
    }
    
    close_db();
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
        <script type="text/javascript">

            var selectedRow = null;

            var rules = new Array('.P', '.D', '.C', '.A');
            var infos = new Array('.por_info', '.dif_info', '.cen_info', '.att_info');

            $(document).ready(function() {
                
                // Cambiamento numero di giocatori nella formazione
                $('#modulo').change(function() {
                    var modulo = $(this).val().split("-");
                    var dif = $('#difensori_titolari tr');
                    var cen = $('#centrocampisti_titolari tr');
                    var att = $('#attaccanti_titolari tr');
                    if(dif.length > modulo[0]) {    // Elimino difensori
                        for(var i = modulo[0]; i < dif.length; i++) {
                            var name = $(dif).eq(i).children('.player').html();
                            showPlayer(name);
                            $(dif).eq(i).remove();
                        }
                    } else {                        // Aggiungo difensori
                        for(var i = dif.length; i < modulo[0]; i++) {
                            $('#difensori_titolari').append('<tr class="D" id="dif_' + i + '">\n\
                                        <td><img src="teams/default.gif" class="default"/></td>\n\
                                        <td class="player titolare"></td></tr>');
                        }
                    }
                    if(cen.length > modulo[1]) {    // Elimino centrocampisti
                        for(var i = modulo[1]; i < cen.length; i++) {
                            var name = $(cen).eq(i).children('.player').html();
                            showPlayer(name);
                            $(cen).eq(i).remove();
                        }
                    } else {                        // Aggiungo centrocampisti
                        for(var i = cen.length; i < modulo[1]; i++) {
                            $('#centrocampisti_titolari').append('<tr class="C" id="cen_' + i + '">\n\
                                        <td><img src="teams/default.gif" class="default"/></td>\n\
                                        <td class="player titolare"></td></tr>');
                        }
                    }
                    if(att.length > modulo[2]) {    // Elimino attaccanti
                        for(var i = modulo[2]; i < att.length; i++) {
                            var name = $(att).eq(i).children('.player').html();
                            showPlayer(name);
                            $(att).eq(i).remove();
                        }
                    } else {                        // Aggiungo attaccanti
                        for(var i = att.length; i < modulo[2]; i++) {
                            $('#attaccanti_titolari').append('<tr class="A" id="att_' + i + '">\n\
                                        <td><img src="teams/default.gif" class="default"/></td>\n\
                                        <td class="player titolare"></td></tr>');
                        }
                    }
                    // Aggiornamento tooltips
                    setTooltips(true);
                });

                // Caricamento giocatori titolari probabili
                loadPlaying();
                
                <? if(!$time_expired) { ?>
                // Creazione tooltips per selezione giocatori
                setTooltips();

                // Creazione eventi di selezione giocatori suggeriti
                setSelectionEvents();

                // Creazione eventi di cancellazione giocatori
                setDeletionEvents();
                <? } ?>

                // Se la formazione è già presente, inizializza i tooltips nascondendo i giocatori presenti
                initPreviousFormation();

                // Compilazione form e invio formazione
                $('#formation_form').submit(function() {
                    $(this).children("[type=hidden]").remove();
                    if($('.default').length > 0) {
                        alert('Completa la formazione con tutti i titolari e i panchinari!');
                        return false;
                    } else {
                        $('.titolare').each(function() {
                            $('#formation_form').append('<input type="hidden" name="giocatori[]" value="' + $(this).html() + '"/>');
                        });
                        $('.ris_0').each(function() {
                            $('#formation_form').append('<input type="hidden" name="prime_riserve[]" value="' + $(this).html() + '"/>');
                        });
                        $('.ris_1').each(function() {
                            $('#formation_form').append('<input type="hidden" name="seconde_riserve[]" value="' + $(this).html() + '"/>');
                        });
                        $('#formation_form').append('<input type="hidden" name="action" value="insert"/>');
                        return confirm("\t\t!! ATTENZIONE !!\nConfermi la formazione?");
                    }
                });
            });

            function showPlayer(name) {
                if(name != '') {
                    // Rendo nuovamente visibile il giocatore deselezionato (in tutti i tooltips)
                    var desel = $('[title=' + name + ']');
                    if(desel.length != 0)
                        desel.each(function() {
                            $(this).css("display", "table-row");
                        });
                }
            }

            function hidePlayer(name) {
                if(name != '') {
                    var sel = $('[title=' + name + ']');
                        sel.each(function() {
                            $(this).css("display", "none");
                        });
                }
            }

            function setSelectionEvents() {
                var sugg = new Array('.por_sugg', '.dif_sugg', '.cen_sugg', '.att_sugg');
                for(var i = 0; i < sugg.length; i++)
                    $(sugg[i]).click(function() {
                        // Rendo nuovamente visibile il giocatore deselezionato (in tutti i tooltips)
                        var name = $(selectedRow).attr("id");
                        showPlayer(name);
                        
                        // Determino la tipologia del giocatore
                        var type = '';
                        if($(selectedRow).children('.player').hasClass("titolare"))
                            type = 'titolare';
                        else if($(selectedRow).children('.player').hasClass("ris_0"))
                            type = 'ris_0';
                        else if($(selectedRow).children('.player').hasClass("ris_1"))
                            type = 'ris_1';
                        
                        // Seleziono il nuovo
                        $(selectedRow).html($(this).html());
                        $(selectedRow).attr("id", $(this).attr("title"));
                        $(selectedRow).children('.player').addClass(type);

                        $('.qtip').qtip("hide");

                        // Rendo invisibile quello selezionato (in tutti i tooltips)
                        var name = $(this).attr("title");
                        hidePlayer(name);

                        // Rende visibile l'opzione di cancellazione
                        $(this).siblings('.delete').css("display", "table-row");
                    });
            }

            function setDeletionEvents() {
                // Gestione cancellazione giocatori
                $('.delete').click(function() {
                    // Rendo nuovamente visibile il giocatore deselezionato (in tutti i tooltips)
                    var name = $(selectedRow).attr("id");
                    showPlayer(name);

                    // Determino il tipo di giocatore
                    var type = '';
                    if($(selectedRow).children('.player').hasClass("titolare"))
                        type = 'titolare';
                    else if($(selectedRow).children('.player').hasClass("ris_0"))
                        type = 'ris_0';
                    else if($(selectedRow).children('.player').hasClass("ris_1"))
                        type = 'ris_1';

                    // Imposto il contenuto di default della riga
                    $(selectedRow).html('<td><img src="teams/default.gif" class="default"/></td>\n\
                                        <td class="player ' + type + '"></td>');
                    $(this).css("display", "none");

                    $('.qtip').qtip("hide");
                });
            }

            function setTooltips(refresh) {
                if(refresh)
                    $(".qtip").qtip("destroy");
                for(var i = 0; i < rules.length; i++) {
                    $(rules[i]).each(function() {
                        $(this).qtip({
                            content : $(infos[i]),
                            hide : 'unfocus',
                            position : {
                                adjust : {
                                    x : -40,
                                    y : -10,
                                    screen : true
                                }
                            },
                            style : {
                                border : {
                                    width : 3,
                                    radius : 3,
                                    color : '#BBF8AE'
                                },
                                name : 'light'
                            },
                            show : {
                                when : {event : 'click'},
                                effect : {type : 'fade', length : 50},
                                delay : 0
                            }
                        });
                    });
                    // Memorizzo la riga sulla quale ho clickato
                    $(rules[i]).click(function() {
                        selectedRow = $(this);
                    });
                }
            }

            function initPreviousFormation() {
                $('.titolare, .ris_0, .ris_1').each(function() {
                    var name = $(this).html();
                    hidePlayer(name);
                });
            }
            
            function loadPlaying() {
                $.ajax({
                    type: 'GET',
                    url: '<?= $baseUrl ?>engine/api.php',
                    data: {
                        action: "titolari",
                        teamPlayers: '<?= json_encode($all_players) ?>'
                    },
                    dataType: 'json',
                    timeout: 60000,
                    success: function(players)
                    {
                        $('.spinner').hide();
                        for(var i = 0; i < players.length; i++)
                        {   
                            var name = players[i]['nome'];
                            var team = players[i]['squadra'];
                            var role = players[i]['ruolo'];
                            var g = players[i]['g'] ? 'ok' : 'no';
                            var fg = players[i]['fg'] ? 'ok' : 'no';
                            var gol = (role === 'P') ? players[i]['gs'] : players[i]['gf'];
                            var s = '<tr class="' + role + 'l" align="center">\n\
                                        <td>\n\
                                            <a class="fotosq" href="serieateam.php?t=' + team + '">\n\
                                                <img style="width: 14px; height: 14px;" src="teams/' + team + '.gif" alt="' + team + '" title="' + team + '"/>\n\
                                            </a>\n\
                                        </td>\n\
                                        <td>\n\
                                            <a class="linkrsgiol" href="stat_giocatore.php?n=' + name + '" alt="Click x statistiche">' + name + '</a>\n\
                                        </td>\n\
                                        <td><img src="images/' + g + '.png"/></td>\n\
                                        <td><img src="images/' + fg + '.png"/></td>\n\
                                        <td style="font-weight: bold; color: #054BB3;">' + players[i]['media'] + '</td>\n\
                                        <td style="font-weight: bold; font-style: italic;">' + players[i]['mediabm'] + '</td>\n\
                                        <td>' + gol + '</td>\n\
                                        <td>' + players[i]['assist'] + '</td>\n\
                                        <td>' + players[i]['amm'] + '</td>\n\
                                        <td>' + players[i]['esp'] + '</td>\n\
                                        <td>' + players[i]['presenze'] + '</td>\n\
                                    </tr>';
                            $('#probabili tbody').append(s);
                        }
                    },
                    error: function(data)
                    {
                        $('.spinner td').html('Errore');
                    }
                });
            }
        </script>
        <style type="text/css">
            table {
                border-collapse: collapse;
            }
            #probabili th, #probabili td {
                padding-left: 4px;
                padding-right: 4px;
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
                <h2>Formazione [<?echo $team?>] - Giornata n° <?echo $giornata?> </h2>
                <!-- VISUALIZZAZIONE FORMAZIONE -->
                <br/><br/>
                <h4 align="center" style="font-weight: bold; color: #FF2E00; font-size: 12px; background-color: lightgoldenRodYellow;">Click sulle caselle per selezionare o cambiare i giocatori!</h4>
                <? if($time_expired) { ?>
                <h3 align="center" style="font-size: 11pt; margin-bottom: 6px;">L'orario limite per inserire la formazione è stato superato.</h3>
                <? } ?>
                <? if($presente) { ?>
                <h4 align="center">La formazione per la giornata corrente &egrave; stata gi&agrave; inserita!</h4>
                <h4 align="center">Ultima modifica: <strong style="color: red; font-size: 16px;"> <?echo $orario_last?></strong></h4><br/>
                <? } ?>
                
                <? if(!$time_expired) { ?>
                <!-- Selezione modulo -->
                <table align="center">
                    <tr align="center">
                        <td><label for="modulo">Seleziona modulo: </label></td>
                        <td>
                            <select size="1" cols="4" name="modulo" id="modulo">
                                <option selected="selected" value="3-4-3">3-4-3</option>
                                <option value="3-5-2">3-5-2</option>
                                <option value="4-3-3">4-3-3</option>
                                <option value="4-4-2">4-4-2</option>
                                <option value="4-5-1">4-5-1</option>
                                <option value="5-3-2">5-3-2</option>
                                <option value="5-4-1">5-4-1</option>
                            </select>
                        </td>
                    </tr>
                    <tr><td></td></tr>
                </table><br/>
                <? } ?>
                
                <table align="center">
                    <thead>
                        <tr><td>&nbsp;</td><td style="text-align: center; font-weight: bold; padding-left: 100px;">Probabili titolari</td></tr>
                    </thead>
                    <tr>
                        <td style="vertical-align: top;">
                            <!-- Tabella principale per la formazione -->
                            <table align="center">
                                <tr align="center"><td colspan="2" style="font-weight: bold; background-color: #5FD959; color: black;">Portieri</td></tr>
                                <tr valign="top">
                                    <td>
                                        <table>
                                            <? if($presente) { ?>
                                                <tr class="P" id="<?echo $form['TITOLARE']['P'][0][1]?>">
                                                    <td><img src="<?echo "teams/".$form['TITOLARE']['P'][0][0].".gif"?>" alt="<?echo $form['TITOLARE']['P'][0][1]?>"/></td>
                                                    <td class="player titolare"><?echo $form['TITOLARE']['P'][0][1]?></td>
                                                </tr>
                                            <? } else { ?>
                                                <tr class="P" id="por_0">
                                                    <td><img src="<?echo "teams/default.gif"?>" class="default"/></td>
                                                    <td class="player titolare"></td>
                                                </tr>
                                            <? } ?>
                                        </table>
                                    </td>
                                    <td>
                                        <table>
                                            <? if($presente) { ?>
                                                <tr class="P" id="<?echo $form['PRIMA_RISERVA']['P'][0][1]?>">
                                                    <td><img src="<?echo "teams/".$form['PRIMA_RISERVA']['P'][0][0].".gif"?>" alt="<?echo $form['PRIMA_RISERVA']['P'][0][0]?>"/></td>
                                                    <td class="player ris_0"><?echo $form['PRIMA_RISERVA']['P'][0][1]?></td>
                                                </tr>
                                            <? } else { ?>
                                                <tr class="P" id="por_ris_0">
                                                    <td><img src="<?echo "teams/default.gif"?>" class="default"/></td>
                                                    <td class="player ris_0"></td>
                                                </tr>
                                            <? } ?>                                
                                        </table>
                                    </td>
                                </tr>
                                <tr align="center"><td colspan="2" style="font-weight: bold; background-color: #5FD959; color: black;">Difensori</td></tr>
                                <tr valign="top">
                                    <td>
                                        <table id="difensori_titolari">
                                            <? if($presente) { ?>
                                                <? for($i = 0; $i < count($form['TITOLARE']['D']); $i++) { ?>
                                                <tr class="D" id="<?echo $form['TITOLARE']['D'][$i][1]?>">
                                                    <td><img src="<?echo "teams/".$form['TITOLARE']['D'][$i][0].".gif"?>"/></td>
                                                    <td class="player titolare"><?echo $form['TITOLARE']['D'][$i][1]?></td>
                                                </tr>
                                                <? } ?>
                                            <? } else { ?>
                                                <? for($i = 0; $i < $default_dif; $i++) { ?>
                                                <tr class="D" id="dif_<? echo $i ?>">
                                                    <td><img src="<?echo "teams/default.gif"?>" class="default"/></td>
                                                    <td class="player titolare"></td>
                                                </tr>
                                                <? } ?>
                                            <? } ?>
                                        </table>
                                    </td>
                                    <td>
                                        <table>
                                            <? if($presente) { ?>
                                                <tr class="D" id="<?echo $form['PRIMA_RISERVA']['D'][0][1]?>">
                                                    <td><img src="<?echo "teams/".$form['PRIMA_RISERVA']['D'][0][0].".gif"?>"/></td>
                                                    <td class="player ris_0"><?echo $form['PRIMA_RISERVA']['D'][0][1]?></td>
                                                </tr>
                                                <tr class="D" id="<?echo $form['SECONDA_RISERVA']['D'][0][1]?>">
                                                    <td><img src="<?echo "teams/".$form['SECONDA_RISERVA']['D'][0][0].".gif"?>"/></td>
                                                    <td class="player ris_1"><?echo $form['SECONDA_RISERVA']['D'][0][1]?></td>
                                                </tr>
                                            <? } else { ?>
                                                <tr class="D" id="dif_ris_0">
                                                    <td><img src="<?echo "teams/default.gif"?>" class="default"/></td>
                                                    <td class="player ris_0"></td>
                                                </tr>
                                                <tr class="D" id="dif_ris_1">
                                                    <td><img src="<?echo "teams/default.gif"?>" class="default"/></td>
                                                    <td class="player ris_1"></td>
                                                </tr>
                                            <? } ?>
                                        </table>
                                    </td>
                                </tr>
                                <tr align="center"><td colspan="2" style="font-weight: bold; background-color: #5FD959; color: black;">Centrocampisti</td></tr>
                                <tr valign="top">
                                    <td>
                                        <table id="centrocampisti_titolari">
                                            <? if($presente) { ?>
                                                <? for($i=0; $i<count($form['TITOLARE']['C']); $i++) { ?>
                                                <tr class="C" id="<?echo $form['TITOLARE']['C'][$i][1]?>">
                                                    <td><img src="<?echo "teams/".$form['TITOLARE']['C'][$i][0].".gif"?>"/></td>
                                                    <td class="player titolare"><?echo $form['TITOLARE']['C'][$i][1]?></td>
                                                </tr>
                                                <? } ?>
                                            <? } else { ?>
                                                <? for($i = 0; $i < $default_cen; $i++) { ?>
                                                <tr class="C" id="cen_<? echo $i ?>">
                                                    <td><img src="<?echo "teams/default.gif"?>" class="default"/></td>
                                                    <td class="player titolare"></td>
                                                </tr>
                                                <? } ?>
                                            <? } ?>
                                        </table>
                                    </td>
                                    <td>
                                        <table>
                                            <? if($presente) { ?>
                                                <tr class="C" id="<?echo $form['PRIMA_RISERVA']['C'][0][1]?>">
                                                    <td><img src="<?echo "teams/".$form['PRIMA_RISERVA']['C'][0][0].".gif"?>"/></td>
                                                    <td class="player ris_0"><?echo $form['PRIMA_RISERVA']['C'][0][1]?></td>
                                                </tr>
                                                <tr class="C" id="<?echo $form['SECONDA_RISERVA']['C'][0][1]?>">
                                                    <td><img src="<?echo "teams/".$form['SECONDA_RISERVA']['C'][0][0].".gif"?>"/></td>
                                                    <td class="player ris_1"><?echo $form['SECONDA_RISERVA']['C'][0][1]?></td>
                                                </tr>
                                            <? } else { ?>
                                                <tr class="C" id="cen_ris_0">
                                                    <td><img src="<?echo "teams/default.gif"?>" class="default"/></td>
                                                    <td class="player ris_0"></td>
                                                </tr>
                                                <tr class="C" id="cen_ris_1">
                                                    <td><img src="<?echo "teams/default.gif"?>" class="default"/></td>
                                                    <td class="player ris_1"></td>
                                                </tr>
                                            <? } ?>
                                        </table>
                                    </td>
                                </tr>
                                <tr align="center"><td colspan="2" style="font-weight: bold; background-color: #5FD959; color: black;">Attaccanti</td></tr>
                                <tr valign="top">
                                    <td>
                                        <table id="attaccanti_titolari">
                                            <? if($presente) { ?>
                                                <? for($i = 0; $i < count($form['TITOLARE']['A']); $i++) { ?>
                                                <tr class="A" id="<?echo $form['TITOLARE']['A'][$i][1]?>">
                                                    <td><img src="<?echo "teams/".$form['TITOLARE']['A'][$i][0].".gif"?>"/></td>
                                                    <td class="player titolare"><?echo $form['TITOLARE']['A'][$i][1]?></td>
                                                </tr>
                                                <? } ?>
                                            <? } else { ?>
                                                <? for($i = 0; $i < $default_att; $i++) { ?>
                                                <tr class="A" id="att_<? echo $i ?>">
                                                    <td><img src="<?echo "teams/default.gif"?>" class="default"/></td>
                                                    <td class="player titolare"></td>
                                                </tr>
                                                <? } ?>
                                            <? } ?>
                                        </table>
                                    </td>
                                    <td>
                                        <table>
                                            <? if($presente) { ?>
                                                <tr class="A" id="<?echo $form['PRIMA_RISERVA']['A'][0][1]?>">
                                                    <td><img src="<?echo "teams/".$form['PRIMA_RISERVA']['A'][0][0].".gif"?>"/></td>
                                                    <td class="player ris_0"><?echo $form['PRIMA_RISERVA']['A'][0][1]?></td>
                                                </tr>
                                                <tr class="A" id="<?echo $form['SECONDA_RISERVA']['A'][0][1]?>">
                                                    <td><img src="<?echo "teams/".$form['SECONDA_RISERVA']['A'][0][0].".gif"?>"/></td>
                                                    <td class="player ris_1"><?echo $form['SECONDA_RISERVA']['A'][0][1]?></td>
                                                </tr>
                                            <? } else { ?>
                                                <tr class="A" id="att_ris_0">
                                                    <td><img src="<?echo "teams/default.gif"?>" class="default"/></td>
                                                    <td class="player ris_0"></td>
                                                </tr>
                                                <tr class="A" id="att_ris_1">
                                                    <td><img src="<?echo "teams/default.gif"?>" class="default"/></td>
                                                    <td class="player ris_1"></td>
                                                </tr>
                                            <? } ?>
                                        </table>
                                    </td>
                                </tr>
                                <tr><td><br/></td></tr>
                                <? if(!$time_expired) { ?>
                                <tr align="center">
                                    <td colspan="2">
                                        <!-- Form compilato in Javascript per l'invio della formazione -->
                                        <form action="formazione.php" method="post" id="formation_form">
                                            <input align="center" class="mybutton" type="submit" name="submit" value="Invia"/>
                                        </form>
                                    </td>
                                </tr>
                                <? } ?>
                            </table>
                        </td>
                        <td style="vertical-align: top; padding-left: 100px;">
                            <!-- Tabella per probabili titolari -->
                            <table id="probabili" class="rosa" align="center" style="text-align: center; font-size: 85%;">
                                <thead>
                                    <tr style="color: black; font-weight: bold; background-color: #5FD959;">
                                        <th colspan="2">Giocatore</th>
                                        <th><a class="linksqrose" style="font-size: 9px;" href="<?= $url_probform_gazzetta ?>">G.it</a></th>
                                        <th><a class="linksqrose" style="font-size: 9px;" href="<?= $url_probform_fantagazzetta ?>">FG.com</a></th>
                                        <th>VM</th>
                                        <th>VM+</th>
                                        <th>G</th>
                                        <th>A</th>
                                        <th>Am</th>
                                        <th>Es</th>
                                        <th>Pr</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="spinner" style="text-align: center; padding-top: 20px;">
                                        <td colspan="11"><img style="padding-top: 20px;" src="images/spinner.gif" alt="Loading..."/></td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </table>
                <br/><br/><br/>

                <!-- Info nascoste (squadra e nome) dei giocatori usate per tooltip -->
                <div id="players_info" style="display: none">
                    <div class="por_info">
                        <table>
                            <? for($i = 0; $i < count($rosa['P']); $i++) { ?>
                                <tr class="por_sugg" title="<? echo $rosa['P'][$i][1] ?>">
                                    <td><img src="teams/<? echo $rosa['P'][$i][0] ?>.gif"/></td>
                                    <td class="player"><? echo $rosa['P'][$i][1] ?></td>
                                </tr>
                            <? } ?>
                                <!-- Riga per cancellazione giocatore -->
                                <tr class="delete">
                                    <td><img src="<?echo "teams/default.gif"?>"/></td>
                                    <td>-- Cancella --</td>
                                </tr>
                        </table>
                    </div>
                    <div class="dif_info">
                        <table>
                            <? for($i = 0; $i < count($rosa['D']); $i++) { ?>
                                <tr class="dif_sugg" title="<? echo $rosa['D'][$i][1] ?>">
                                    <td><img src="teams/<? echo $rosa['D'][$i][0] ?>.gif"/></td>
                                    <td class="player"><? echo $rosa['D'][$i][1] ?></td>
                                </tr>
                            <? } ?>
                                <!-- Riga per cancellazione giocatore -->
                                <tr class="delete">
                                    <td><img src="<?echo "teams/default.gif"?>"/></td>
                                    <td>-- Cancella --</td>
                                </tr>
                        </table>
                    </div>
                    <div class="cen_info">
                        <table>
                            <? for($i = 0; $i < count($rosa['C']); $i++) { ?>
                                <tr class="cen_sugg" title="<? echo $rosa['C'][$i][1] ?>">
                                    <td><img src="teams/<? echo $rosa['C'][$i][0] ?>.gif"/></td>
                                    <td class="player"><? echo $rosa['C'][$i][1] ?></td>
                                </tr>
                            <? } ?>
                                <!-- Riga per cancellazione giocatore -->
                                <tr class="delete">
                                    <td><img src="<?echo "teams/default.gif"?>"/></td>
                                    <td>-- Cancella --</td>
                                </tr>
                        </table>
                    </div>
                    <div class="att_info">
                        <table>
                            <? for($i = 0; $i < count($rosa['A']); $i++) { ?>
                                <tr class="att_sugg" title="<? echo $rosa['A'][$i][1] ?>">
                                    <td><img src="teams/<? echo $rosa['A'][$i][0] ?>.gif"/></td>
                                    <td class="player"><? echo $rosa['A'][$i][1] ?></td>
                                </tr>
                            <? } ?>
                                <!-- Riga per cancellazione giocatore -->
                                <tr class="delete">
                                    <td><img src="<?echo "teams/default.gif"?>"/></td>
                                    <td>-- Cancella --</td>
                                </tr>
                        </table>
                    </div>
                </div>
                
            </div>
        </div>
        <div id="footer">
            <p>Copyright (c) 2009 <a href="http://www.andreamartelli.it">Andrea Martelli</a>. All rights reserved.</p>
        </div>
    </body>
</html>