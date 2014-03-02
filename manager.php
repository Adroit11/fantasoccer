<?php

include_once dirname(__FILE__) . "/engine/engine.php";
//include_once( "engine/engine.php" );


if(empty($_SESSION['tipo']) || $_SESSION['tipo']!='MANAGER') {
    access_denied();
}
if($_SESSION['tipo']=='MANAGER') {
    if($_GET['action']=='giocatori') {
        // Creazione 'giocatori' - Download ed estrazione
        $players_file = http_get_file($url_playerlist);
        if(!$players_file) {
            system_error("Errore: impossibile scaricare la lista giocatori");
        }
        $players = parse_players_table($players_file);
        unset($players_file);

        // Aggiornamento tabella 'giocatori' - TODO: non solo nuovi,ma anche cambi squadra!!
        start_transaction();
        // 1) Inserimento nuovi
        $first = true;
        $query = 'INSERT IGNORE INTO giocatori(nome, ruolo, seriea) VALUES';
        for($i=0; $i<count($players); $i++) {
            if (!$first) $query .= ', ';
            $query .= " ('".$players[$i][0]."','".$players[$i][1]."','".$players[$i][2]."')";
            $first = false;
        }
        $newf = mysql_query($query, $mysql);
        if(!$newf) {
            rollback();
            system_error("Errore nell'inserimento dei nuovi giocatori");
        }
        // 2) Rimozione giocatori assenti
        $elencodb = mysql_query("SELECT nome, seriea FROM giocatori", $mysql);
        while($r = mysql_fetch_row($elencodb)) {
            $found = false;
            $changedteam = false;
            $newteam = null;
            for($i=0; $i<count($players) && !$found; $i++) {
                if($r[0]==$players[$i][0]) {
                    $found = true;
                    if($r[1]!=$players[$i][2]) {
                        $changedteam = true;
                        $newteam = $players[$i][2];
                    }
                }
            }
            if(!$found)
                // Non elimino il giocatore, ma ne cancello l'appartenenza alla squadra
                mysql_query("UPDATE giocatori SET squadra=NULL WHERE nome='$r[0]'", $mysql);
            else if($changedteam)
                // Aggiorno la squadra di serie A alla quale il giocatore appartiene
                mysql_query("UPDATE giocatori SET seriea='$newteam' WHERE nome='$r[0]'", $mysql);
        }
        commit();

        stream_event_put('PLAYERS', $giornata);

        close_db();

        // Inserimento evento nello stream

        header("location: manager.php");
        
    } else if($_GET['action'] == 'back' || $_GET['action'] == 'backpartial') {
        // Torna indietro di una giornata:
        //  - cancella risultati calendario
        //  - modifica come non giocata la giornata
        //  - cancella tutti i voti dei giocatori
        //  Opzionalmente:
        //  - cancella formazioni definitive
        //  - cancella formazioni temporanee
        if($_GET['action']=='back')
            $full = true;
        $result = mysql_query("SELECT MAX(n) FROM giornate WHERE giocata=TRUE", $mysql);
        if(!$result) header("location: error.php?message=2");
        $result = mysql_fetch_row($result);
        $giornata = $result[0];

        start_transaction();

        $ok1 = mysql_query("UPDATE calendario SET punti1=NULL, punti2=NULL, gol1=NULL, gol2=NULL, risultato1=NULL, risultato2=NULL WHERE giornata=$giornata", $mysql);
        $ok2 = mysql_query("UPDATE giornate SET giocata=FALSE WHERE n=$giornata", $mysql);
        $ok3 = mysql_query("DELETE FROM voti WHERE giornata=$giornata", $mysql);
        if($full) {
            $ok5 = mysql_query("DELETE FROM formazioni_temp", $mysql);

            // preleva le formazioni valide della giornata precedente e le inserisce in formazioni_temp
            if($_GET['action'] == 'backpartial')
                recover_formations($giornata);

            $ok4 = mysql_query("DELETE FROM formazioni WHERE giornata=$giornata", $mysql);
        }
        if(!$ok1 || !$ok2 || !$ok3 || ($full && (!$ok4 || !$ok5))) {
            rollback();
            system_error("Errore nel rollback della giornata");
        }
        commit();
        close_db();
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
        <link rel="shortcut icon" href="pics/favicon.ico"/>
        <? @include("page_elements/scripts.php"); ?>
        <script type="text/javascript">
            function confirm_back() {
                return confirm("\t\t!!! ATTENZIONE !!!\nI risultati dell'ultima giornata disputata verranno cancellati!!\nConfermi?");
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

                <h2 align="center">Pannello di controllo</h2>

                <div id="admin_menu" align="center">
                    <table>
                        <tr>
                            <td>
                                <div class="menu_item" id="giocatori">
                                <a href="manager.php?action=giocatori">
                                    <img src="images/admin/players.png" alt="Download lista giocatori"/>
                                </a>
                                </div>
                            </td>
                            <td>
                                <div class="menu_item" id="giornata">
                                    <a href="aggiorna_giornata.php">
                                        <img src="images/admin/matches.png" alt="Avanza di una giornata"/>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <div class="menu_item" id="giornata_back">
                                    <a href="manager.php?action=back" onclick="return confirm_back();">
                                        <img src="images/admin/back.png" alt="Cancella ultima giornata (tutto)"/>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <div class="menu_item" id="giornata_back_form">
                                    <a href="manager.php?action=backpartial" onclick="return confirm_back();">
                                        <img src="images/admin/back_form.png" alt="Cancella ultima giornata (conserva formazioni)"/>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <div class="menu_item" id="rose">
                                    <a href="inserisci_rosa.php">
                                        <img src="images/admin/rose.png" alt="Inserisci o modifica rose"/>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="menu_item_caption">Download lista giocatori</td>
                            <td class="menu_item_caption">Avanza di una giornata</td>
                            <td class="menu_item_caption">Cancella ultima giornata (tutto)</td>
                            <td class="menu_item_caption">Cancella ultima giornata (conserva formazioni)</td>
                            <td class="menu_item_caption">Inserisci o modifica rose</td>
                        </tr>
                    </table>
                    
                </div>
                
            </div>
        </div>
        <div id="footer">
            <p>Copyright (c) 2009 <a href="http://www.andreamartelli.it">Andrea Martelli</a>. All rights reserved.</p>
        </div>
    </body>
</html>