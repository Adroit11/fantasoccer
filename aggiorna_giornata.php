<?php

include_once dirname(__FILE__) . "/engine/engine.php";
//include_once( "engine/engine.php" );

// TODO:
// - supporto TRANSAZIONI!!

manager_gatekeeper();

// Ottengo il numero della prossima giornata da disputare
$giornata = get_next_sportday();

// Ottengo i nomi delle squadre
$teams = get_team_names();

if(!$_POST['squadre']) {

    // Ottengo gli orari di immissione delle formazioni
    $orari = get_formation_times();

} else {

    // Verifica disponibilità voti
	if(verify_vote_availability($giornata)) {
        
        start_transaction();

        // ALLINEAMENTO FORMAZIONI
        $confirmed = $_POST['squadre'];

        // Array delle squadre senza formazioni valide attuali o precedenti
        $nulle = array();

        foreach($teams as $team) {

            // - Se non c'è una formazione accettata x la squadra
            if(!in_array($team,$confirmed)) {

                // Prendi la formazione precedente e copia per la giornata attuale

                $result = get_last_valid_formation($team, $giornata, $nulle);

                if($result)
                    $copied = copy_formation($team, $giornata, $result);
            }
            // - Se è stata confermata una formazione immessa
            else {
                $time = $_POST["orario_".str_replace(" ", "_", $team)];
                // Copia della formazione temporanea (dell'orario opportuno) come definitiva
                confirm_formation($team, $giornata, $time);
            }
        }
        // Svuotamento formazioni temporanee
        delete_temp_formations();

        // Download e inserimento voti
        $voti = download_votes($giornata);

        // CALCOLO PUNTEGGI DI OGNI SQUADRA

        // Prelevo le formazioni definitive e le organizzo in una struttura dati
        $form = get_formations($giornata);

        foreach($teams as $team) {  // PER OGNI SQUADRA

            if($nulle && in_array($team,$nulle)) {        // Non ha formazione -> punteggio=0

                $punti[$team] = 0;

            } else {

                // Calcolo punteggio
                $punti[$team] = calculate_score($team, $form[$team], $voti);

            }
        }
        // AGGIORNAMENTO CALENDARIO

        // Ottengo la tabella degli scontri diretti
        $sfide = get_sportday_matches($giornata);

        // Calcolo i risultati e aggiorno il calendario
        refresh_calendar($sfide, $punti, $giornata);

        // Notifica dei risultati via Facebook
        notifica_risultati_facebook($sfide, $punti, $giornata);

        // AVANZAMENTO GIORNATA
        next_sportday($giornata);

        // COMMIT
        commit();

        // Inserimento evento nello stream
        stream_event_put('RESULTS_PUB', $giornata);

        header("location: archivio_formazioni.php");
    }

}
close_db();

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
            function validate() {
                return confirm("\t\t!!! ATTENZIONE !!!\nConfermi l'avanzamento di giornata?\nConfermi le squadre selezionate?");
            }
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
                <h2>Aggiornamento giornata N° <?echo $giornata?></h2>
                <form action="aggiorna_giornata.php" method="post" onsubmit="return validate();">
                    <table align="center">
                        <tr style="color: black; font-weight: bold; font-style: italic; background-color: #5FD959" align="center">
                            <td>Squadra</td>
                            <td>Orario inserimento formazione</td>
                            <td>Accetta</td>
                        </tr>
                        <? foreach($teams as $team) { ?>
                        <tr align="center">
                            <td style="color: #0066FF; font-weight: bold;"><?echo $team?></td>
                            <!-- Combo-box x selezione dell'orario da accettare -->
                            <td style="color: #FF7F00; font-weight: bold;">
                                <select size="1" name="orario_<?echo $team?>" id="orario_<?echo $team?>">
                                    <? for($i=0; $i<count($orari[$team]); $i++) { ?>
                                        <option value="<?echo $orari[$team][$i]?>"><?echo $orari[$team][$i]?></option>
                                    <? } ?>
                                </select>
                            </td>
                            <td><input type="checkbox" <? if(!$orari[$team]) echo 'disabled="disabled"'; ?>name="squadre[]" id="squadre[]" value="<?echo $team?>"/> </td>
                        </tr>
                        <? } ?>
                        <tr><td><br/></td></tr>
                        <tr align="center"><td colspan="3"><input class="mybutton" type="submit" name="OK" value="OK"/></td></tr>
                    </table>
                </form>
            </div>
        </div>
        <div id="footer">
            <p>Copyright (c) 2009 <a href="http://www.andreamartelli.it">Andrea Martelli</a>. All rights reserved.</p>
        </div>
    </body>
</html>