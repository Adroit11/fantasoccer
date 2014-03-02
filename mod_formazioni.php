<?php

include_once dirname(__FILE__) . "/engine/engine.php";
//include_once( "engine/engine.php" );

if(empty($_SESSION['tipo']) || empty($_SESSION['username']) || $_SESSION['tipo']=='ADMIN') {

    access_denied();

} else {
    
    $team = $_POST['squadra'];
    if($team && !$_POST['action']) {
        $result = mysql_query("SELECT nome, seriea, ruolo FROM giocatori WHERE squadra='$team'", $mysql);
        if(!$result) system_error("Errore: Impossibile ottenere i giocatori");
        while($r = mysql_fetch_row($result)) {
            $rosa[$r[2]][] = array($r[1],$r[0]);
        }

        // Prelevo eventuale formazione precedente
        // Verifico se la formazione è stata già inserita e nel caso prelevo l'ultima
        $result = mysql_query("SELECT MAX(orario) FROM formazioni_temp WHERE squadra='$team'", $mysql);
        $presente = false;
        $orario_last = null;
        if(!$result) system_error("Errore: Impossibile ottenere gli orari d'inserimento formazione");
        $result = mysql_fetch_row($result);
        $orario_last = $result[0];
        if($orario_last) {
            $presente = true;
            $ALL = mysql_query("SELECT seriea, f.nome, ruolo, tipo FROM giocatori AS g JOIN formazioni_temp AS f ON g.nome=f.nome WHERE f.squadra='$team' AND f.orario='$orario_last'", $mysql);
            if(!$ALL) system_error("Errore: Impossibile ottenere la formazione precedente");
            while($r = mysql_fetch_row($ALL))
                $form[$r[3]][$r[2]][] = array($r[0], $r[1]);
        }
    }
    if(!$_POST['action']) {
        $squadre = mysql_query("SELECT nome FROM squadre", $mysql);
        if(!$squadre) system_error("Errore: Impossibile ottenere elenco squadre");
    }
    $result = mysql_query("SELECT MIN(n) FROM giornate WHERE giocata=FALSE", $mysql);
    if(!$result) system_error("Errore: Impossibile ottenere il numero della prossima giornata");
    $result = mysql_fetch_row($result);
    $giornata = $result[0];

    // Inserimento formazione
    if($_POST['action']=='insert') {
        // Se già presente, elimino la vecchia
        if($team)
            $newf = mysql_query("DELETE FROM formazioni_temp WHERE squadra='$team' AND orario='2999-12-31 23:59:59'", $mysql);

        // Inserisco la nuova formazione come amministratore - orario di default
        $date = "2999-12-31 23:59:59";
        $tit = $_POST['giocatori'];
        $pr = $_POST['prime_riserve'];
        $sr = $_POST['seconde_riserve'];
        $team = $_POST['squadra'];

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
        $res = mysql_query("SELECT presidente FROM squadre WHERE nome='$team'");
        $res = mysql_fetch_assoc($res);
        if($presente) {
            // evento modifica formazione
            stream_event_put('FORM_MOD', $giornata, $res['presidente']);
        } else {
            // evento inserimento formazione
            stream_event_put('FORM_INS', $giornata, $res['presidente']);
        }

        if(!$newf) system_error("Errore: Impossibile inserire la formazione");
        header("location: form_giornata.php");
    }
    close_db();

    $default_dif = 3;
    $default_cen = 4;
    $default_att = 3;
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

                // Creazione tooltips per selezione giocatori
                setTooltips();

                // Creazione eventi di selezione giocatori suggeriti
                setSelectionEvents();

                // Creazione eventi di cancellazione giocatori
                setDeletionEvents();

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
                        $('#formation_form').append('<input type="hidden" name="squadra" value="<?echo $team?>"/>');
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
                <!-- Selezione squadra -->
                <? if(empty($team)) { ?>
                <div class="box">
                    <h2>Seleziona squadra</h2>
                    <form action="mod_formazioni.php" method="post">
                        <table align="center">
                            <tr>
                                <td>
                                    <label for="squadra">Seleziona squadra: </label>
                                </td>
                                <td>
                                    <select size="1" cols="10" name="squadra" id="squadra">
                                        <?php
                                        while($row = mysql_fetch_row($squadre)) {
                                            echo '<option value="'.$row[0].'">'.$row[0];
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr><td height="15"></td></tr>
                            <tr>
                                <td align="center" colspan="2">
                                    <input type="submit" class="mybutton" name="OK" value="OK" title="OK"/>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                <? } else { ?>






                <h2>Formazione [<?echo $team?>] - Giornata n° <?echo $giornata?> </h2>
                <!-- VISUALIZZAZIONE FORMAZIONE -->
                <br/><br/>
                <h4 align="center" style="font-weight: bold; color: #FF2E00; font-size: 12px; background-color: lightgoldenRodYellow;">Click sulle caselle per selezionare o cambiare i giocatori!</h4>
                <? if($presente) { ?>
                <h4 align="center">La formazione per la giornata corrente &egrave; stata gi&agrave; inserita!</h4>
                <h4 align="center">Ultima modifica: <strong style="color: red; font-size: 16px;"> <?echo $orario_last?></strong></h4><br/>
                <? } ?>

                <!-- Selezione modulo -->
                <table align="center">
                    <tr align="center">
                        <td><label for="modulo">Seleziona modulo: </label></td>
                        <td>
                            <select size="1" cols="4" name="modulo" id="modulo">
                                <option selected="selected" value="3-4-3">3-4-3
                                <option value="3-5-2">3-5-2
                                <option value="4-3-3">4-3-3
                                <option value="4-4-2">4-4-2
                                <option value="4-5-1">4-5-1
                                <option value="5-3-2">5-3-2
                                <option value="5-4-1">5-4-1
                            </select>
                        </td>
                    </tr>
                    <tr><td></td></tr>
                </table><br/>

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
                    <tr align="center">
                        <td colspan="2">
                            <!-- Form compilato in Javascript per l'invio della formazione -->
                            <form action="mod_formazioni.php" method="post" id="formation_form">
                                <input align="center" class="mybutton" type="submit" name="submit" value="Invia"/>
                                <!--<input type="hidden" name="squadra" value="<? //echo $squadra ?>" />-->
                            </form>
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






                <? } ?>
                <br/><br/>
            </div>
        </div>
        <div id="footer">
            <p>Copyright (c) 2009 <a href="http://www.andreamartelli.it">Andrea Martelli</a>. All rights reserved.</p>
        </div>
    </body>
</html>