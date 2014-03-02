<?php

include_once dirname(__FILE__) . "/engine.php";

/**
 * CRON JOB
 * Verifica la disponibilità di voti per la giornata in corso
 * ed eventualmente aggiorna tutto.
 */

// Ottengo il numero della prossima giornata da disputare
$giornata = get_next_sportday();

// Ottengo i nomi delle squadre
$teams = get_team_names();

echo "Controllo disponibilita voti giornata [$giornata]...";

if(verify_vote_availability_bg($giornata))
{
    echo "Voti disponibili!";
    start_transaction();

    // ALLINEAMENTO FORMAZIONI

    // Array delle squadre senza formazioni valide attuali o precedenti
    $nulle = array();

    echo "Allineamento formazioni...";
    foreach($teams as $team)
    {
        // TODO: funzione per ottenere l'orario dell'ultima formazione temporanea
        $time = get_last_temp_formation_validtime($team);
        echo "Squadra [$team]: ultimo inserimento formazione: $time";
        
        if($time === false)
        {
            echo "Squadra [$team]: formazione non inserita. Recuper dell'ultima utile...";
            // Prendi la formazione precedente e copia per la giornata attuale
            $result = get_last_valid_formation($team, $giornata, $nulle);

            if($result)
            {
                $copied = copy_formation($team, $giornata, $result);
                echo "Squadra [$team]: formazione recuperata";
            }
            else
            {
                echo "Squadra [$team]: non esistono formazioni inserite in questo campionato";
            }
        }
        else
        {
            echo "Squadra [$team]: conferma della formazione inserita in data: $time";
            // Copia della formazione temporanea (dell'orario opportuno) come definitiva
            confirm_formation($team, $giornata, $time);
        }
    }
    
    echo "Cancellazione formazioni temporanee...";
    // Svuotamento formazioni temporanee
    delete_temp_formations();

    echo "Download voti...";
    // Download e inserimento voti
    $voti = download_votes($giornata);

    // CALCOLO PUNTEGGI DI OGNI SQUADRA
    
    echo "Analisi formazioni di giornata...";
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

    echo "Aggiornamento calendario...";
    // Ottengo la tabella degli scontri diretti
    $sfide = get_sportday_matches($giornata);

    // Calcolo i risultati e aggiorno il calendario
    refresh_calendar($sfide, $punti, $giornata);

    // Notifica dei risultati via Facebook
    notifica_risultati_facebook($sfide, $punti, $giornata);

    echo "Avanzamento di giornata...";
    // AVANZAMENTO GIORNATA
    next_sportday($giornata);

    // COMMIT
    commit();
    
    // Inserimento evento nello stream
    stream_event_put('RESULTS_PUB', $giornata);
    
    echo "COMPLETATO.";
}
else
{
    echo "Voti per la giornata [$giornata] non ancora disponibili";
}

close_db();

?>
