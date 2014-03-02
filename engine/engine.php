<?php
/*
 * Funzioni di libreria generali
 *
 * @author Andrea Martelli
 */

session_start();

include_once dirname(__FILE__) . "/config.php";
include_once dirname(__FILE__) . "/database.php";
include_once dirname(__FILE__) . "/parsers.php";
include_once(dirname(dirname(__FILE__)) . "/facebook/fb_interface.php");

/**
 * Controllo d'accesso generico, verifica se l'utente è loggato
 */
function gatekeeper() {

    global $baseUrl;
    if(empty($_SESSION['tipo']) || empty($_SESSION['username']))
        access_denied();
}

function isloggedin() {
    return ( isset($_SESSION['tipo']) && isset($_SESSION['username']) );
}

/**
 * Permette l'accesso solo agli utenti 'semplici'
 */
function user_gatekeeper() {

    global $baseUrl;
    if(empty($_SESSION['tipo']) || empty($_SESSION['username']) || $_SESSION['tipo']=='ADMIN')
        access_denied();
}

/**
 * Permette l'accesso solo al 'Manager'
 */
function manager_gatekeeper() {

    global $baseUrl;
    if(empty($_SESSION['tipo']) || $_SESSION['tipo'] != 'MANAGER')
        access_denied();
}

function ismanagerloggedin() {
    return ( $_SESSION['tipo'] && $_SESSION['tipo'] == 'MANAGER' );
}

/**
 * Permette l'accesso solo all' 'Admin'
 */
function admin_gatekeeper() {
    global $baseUrl;
    if(empty($_SESSION['tipo']) || $_SESSION['tipo'] != 'ADMIN')
        access_denied();
}

function isadminloggedin() {
    return ( $_SESSION['tipo'] && $_SESSION['tipo']=='ADMIN' );
}

/**
 * Reindirizza alla pagina di accesso vietato
 */
function access_denied() {
    global $baseUrl;
    header("location: {$baseUrl}access_denied.php");
}

/**
 * Restituisce il numero della prossima giornata da disputare
 */
function get_next_sportday() {

    global $mysql;
    $result = mysql_query("SELECT MIN(n) FROM giornate WHERE giocata=FALSE", $mysql);
    if(!$result)
        system_error('Errore nell\'ottenere informazioni sulla giornata');
    $result = mysql_fetch_row($result);
    $giornata = $result[0];

    return $giornata;
}

/**
 * Verifica che non sia scaduto l'orario limite per l'inserimento della formazione
 */
function sportday_time_expired($n)
{
    global $mysql, $timeZoneOffset;
    $ok = false;
    $result = mysql_query("SELECT DATE_SUB((SELECT timelimit 
                                            FROM giornate 
                                            WHERE n=$n), 
                                           INTERVAL 10 MINUTE) > (NOW()+INTERVAL $timeZoneOffset HOUR)");
    
    if(!$result)
        system_error ("Errore nella verifica dell'orario");
    $result = mysql_fetch_row($result);
    return ($result[0] == 0);
}

/**
 * Calcola il tempo rimanente alla prossima giornata
 */
function next_sportday_time()
{
    global $mysql;
    $result = mysql_query("SELECT timelimit 
                           FROM giornate 
                           WHERE n=(SELECT MIN(n) FROM giornate WHERE giocata=FALSE)");
    if(!$result)
        return 0;
    $result = mysql_fetch_row($result);
    $result = $result[0];
    if($result < 0)
        return 0;
    return $result;
}

/**
 * Restituisce il numero dell'ultima giornata disputata
 */
function get_last_sportday() {
    
    global $mysql;
    $result = mysql_query("SELECT MAX(n) FROM giornate WHERE giocata=TRUE", $mysql);
    if(!$result)
        system_error('Errore nell\'ottenere informazioni sulla giornata');
    $result = mysql_fetch_row($result);
    $giornata = $result[0];

    return $giornata;
}

/**
 * Restituisce il numero della prima giornata di campionato
 */
function get_first_sportday() {
    
    global $mysql;
    $result = mysql_query("SELECT MIN(n) FROM giornate", $mysql);
    if(!$result)
        system_error('Errore nell\'ottenere informazioni sulla giornata');
    $result = mysql_fetch_row($result);
    $giornata = $result[0];

    return $giornata;
}

/**
 * Restituisce il numero di giornate giocate
 */

/**
 * Restituisce gli orari in cui sono state inserite le formazioni
 */
function get_formation_times() {

    global $mysql;
    $orariform = mysql_query("SELECT DISTINCT squadra, orario FROM formazioni_temp ORDER BY orario", $mysql);
    if(!$orariform)
        system_error('Errore nell\'ottenimento degli orari di inserimento formazioni');
    while($r = mysql_fetch_row($orariform))
        $orari[$r[0]][] = $r[1];
    return $orari;
}

/**
 * Riepilogo sintetico di medie voto e altri valori per i dati giocatori
 */
function get_summary_for_players($players)
{
    global $mysql;
    
    $result = mysql_query("SELECT nome, COUNT(*), ROUND(AVG(voto), 2), ROUND(AVG(voto+bonusmalus), 2), 
                                SUM(gf), SUM(gs), SUM(assist), SUM(ammonizioni), SUM(espulsioni) 
                           FROM voti 
                           WHERE nome IN('" . implode("','", $players) . "') 
                           GROUP BY nome", $mysql);
    if(!$result)
        return null;
    $summary = array();
    while($r = mysql_fetch_row($result))
    {
        $summary[$r[0]] = array('presenze' => $r[1], 'media' => $r[2], 'mediabm' => $r[3], 
                                'gf' => $r[4], 'gs' => $r[5], 'assist' => $r[6], 
                                'amm' => $r[7], 'esp' => $r[8]);
    }
    foreach($players as $p)
        if(!array_key_exists($p, $summary))
            $summary[$p] = array('presenze' => 0, 'media' => 0, 'mediabm' => 0, 
                                'gf' => 0, 'gs' => 0, 'assist' => 0, 
                                'amm' => 0, 'esp' => 0);
    return $summary;
}

/**
 * Riepilogo completo di tutte le medie per i giocatori di una squadra
 * ordinati in maniera custom
 */
function get_team_players_data($team, $lockroles, $sortfield, $sortorder)
{
    global $mysql;
    
    $_SESSION['lockroles'] = $lockroles;
    
    $query = 'SELECT g.nome, g.seriea, g.ruolo, g.squadra, ROUND(AVG(voto), 2) as mv, 
                                ROUND(AVG(voto+bonusmalus),2) as mvbm, SUM(v.gf) as gf, SUM(v.gs) as gs, SUM(v.assist) as ass, 
                                SUM(v.autogol) as auto, SUM(v.ammonizioni) as amm, SUM(v.espulsioni) as esp, SUM(v.rigpar) as rpar, 
                                SUM(v.rigsba) as rsba, SUM(titolare) as t, COUNT(*) as p 
                           FROM voti AS v JOIN giocatori AS g ON v.nome=g.nome
                           WHERE g.squadra="' . $team . '"
                           GROUP BY v.nome
                           ORDER BY ' . ($lockroles ? 'ruolo ASC,' : '') . ' ' . $sortfield . ' ' . $sortorder;
    
    $result = mysql_query($query, $mysql);
    if(!$result)
        system_error("Errore nel calcolo delle statistiche");
    $table = array();
    while($r = mysql_fetch_assoc($result))
    {
        $table[] = $r;
    }
    return $table;
}

/**
 * Nomi di tutte le squadre
 *
 * @return array di string
 */
function get_team_names() {

    global $mysql;
    $teams = mysql_query("SELECT nome FROM squadre", $mysql);
    if(!$teams)
        system_error("Impossibile prelevare l'elenco delle squadre");

    while($s = mysql_fetch_row($teams))
        $squadre[] = $s[0];

    return $squadre;
}

/**
 * Prende l'ultima formazione valida inserita dall'utente cercandola tra le giornate precedenti
 *
 * @param $squadra
 * @param $giornata
 * @return MySql object
 */
function get_last_valid_formation($squadra, $giornata, &$nulle) {

    global $mysql;
    $result = mysql_query("SELECT nome,tipo,orario FROM formazioni 
                           WHERE squadra='$squadra' AND giornata =
                             (SELECT MAX(giornata) FROM formazioni
                              WHERE squadra='$squadra')
                           AND giornata<$giornata", $mysql);
    if(!$result)
        system_error("Impossibile prelevare l'ultima formazione valida per $squadra");

    if(mysql_num_rows($result)==0) {      // L'utente non ha mai inserito 1 formazione
        $nulle[] = $squadra;           // Vettore contenente le squadre senza nessuna formazione
        return false;
    }

    if(mysql_num_rows($result) == 0)
        return false;
    return $result;
}

/**
 * Prende l'ultima formazione valida inserita dall'utente cercandola tra le giornate precedenti
 *
 * @param $squadra
 * @param $giornata
 * @return MySql object
 */
function get_last_valid_formations($giornata) {

    global $mysql;
    $result = mysql_query("SELECT squadra,nome,tipo,orario FROM formazioni
                           WHERE giornata =
                             (SELECT MAX(giornata) FROM formazioni)
                           AND giornata<$giornata", $mysql);
    if(!$result)
        system_error("Impossibile prelevare le ultime formazioni valide");

    if(mysql_num_rows($result) == 0)
        return false;
    return $result;
}

/**
 * Copia l'ultima formazione utile come formazione della giornata attuale
 *
 * @param <MySql object> $formation    Ultima formazione utile
 * @param <string>       $squadra
 * @param <int>          $giornata
 */
function copy_formation($squadra, $giornata, $formation = null) {

    global $mysql;
    if(!$formation)
        $formation = get_last_valid_formation($squadra, $giornata);
    $first = true;
    $query = 'INSERT INTO formazioni(giornata, squadra, nome, tipo, orario) VALUES';
    while($row = mysql_fetch_row($formation)) {
        if (!$first) $query .= ', ';
        $query .= " ($giornata,'$squadra','$row[0]','$row[1]','$row[2]')";
        $first = false;
    }
    $newf = mysql_query($query, $mysql);
    if(!$newf) {
        rollback();
        system_error("Impossibile allineare le formazioni mancanti a quelle delle giornate precedenti");
    }
    return true;
}

/**
 * In caso di ritorno alla giornata precedente: ripristina la formazione validata come temporanea
 *
 * @param <string>       $squadra
 * @param <int>          $giornata
 */
function recover_formations($giornata) {

    global $mysql;
    $formations = get_last_valid_formations($giornata+1);
    if($formations) {
        $first = true;
        $query = 'INSERT INTO formazioni_temp(squadra, nome, tipo, orario) VALUES';
        while($row = mysql_fetch_row($formations)) {
            if (!$first) $query .= ', ';
            $query .= " ('$row[0]','$row[1]','$row[2]','$row[3]')";
            $first = false;
        }
        $newf = mysql_query($query, $mysql);
        if(!$newf) {
            rollback();
            system_error("Impossibile ripristinare le formazioni della giornata precedente");
        }
        return true;
    } else {
        system_error("Impossibile ripristinare le formazioni della giornata precedente");
    }
}

/**
 * Ottiene l'orario dell'ultima formazione temporanea inserita per la giornata
 * oppure false se non ce n'è nessuna
 * 
 * @param type $squadra
 */
function get_last_temp_formation_validtime($squadra)
{
    global $mysql;
    $result = mysql_query("SELECT MAX(orario) FROM formazioni_temp WHERE squadra='$squadra'");
    if(!$result) {
        rollback();
        system_error("Impossibile ottenere la data dell'ultima formazione temporanea");
    }
    $result = mysql_fetch_row($result);
    if(!$result[0] || $result[0] == null || empty($result[0]) || $result[0] == '')
        return false;
    else
        return $result[0];
}

/**
 * Conferma la formazione temporanea rendendola definitiva
 *
 * @param <string> $squadra
 * @param <int> $giornata
 * @param <string> $time    orario della formazione validata dal manager
 */
function confirm_formation($squadra, $giornata, $time) {
    
    global $mysql;
    $result = mysql_query("SELECT nome,tipo,orario FROM formazioni_temp WHERE squadra='$squadra' AND orario='$time'");
    if(!$result) {
        rollback();
        system_error("Impossibile copiare la formazione temporanea");
    }
    $first = true;
    $query = 'INSERT INTO formazioni(giornata, squadra, nome, tipo, orario) VALUES';
    while($row = mysql_fetch_row($result)) {
        if (!$first) $query .= ', ';
        $query .= " ($giornata,'$squadra','$row[0]','$row[1]','$row[2]')";
        $first = false;
    }
    $newf = mysql_query($query, $mysql);
    if(!$newf) {
        rollback();
        system_error("Impossibile copiare la formazione temporanea come definitiva");
    }
}

function get_formations($giornata) {

    global $mysql;
    $result = mysql_query("SELECT f.squadra, f.nome, seriea, ruolo, tipo FROM formazioni AS f JOIN giocatori AS g ON f.nome=g.nome WHERE giornata=$giornata", $mysql);
    if(!$result) {
        rollback();
        system_error("Errore nell'estrazione delle formazioni");
        return false;
    }
    while($g = mysql_fetch_row($result))
        $form[$g[0]][$g[3]][$g[4]][] = $g[1]; //Squadra->Ruolo->Tipo-> (nome)
    unset($result);
    return $form;
}

/**
 * Cancella tutte le formazioni temporanee di giornata
 */
function delete_temp_formations() {

    global $mysql;
    $ok = mysql_query("DELETE FROM formazioni_temp", $mysql);
    if(!$ok) {
        rollback();
        system_error("Impossibile cancellare le formazioni temporanee");
    }
}

/**
 * Preleva dal db la tabella degli scontri di giornata
 *
 * @param $giornata
 * @return tabella degli scontri
 */
function get_sportday_matches($giornata) {

    global $mysql;
    $matches = mysql_query("SELECT squadra1, squadra2 FROM calendario WHERE giornata=$giornata", $mysql);
    if(!$matches) {
        rollback();
        system_error('Impossibile ottenere la giornata corrente dal calendario');
        return false;
    }
    while($r = mysql_fetch_row($matches))
        $sfide[] = array($r[0], $r[1]);

    return $sfide;
}

/**
 * Calcola a partire dai punteggi, i gol e i risultati di giornata e li
 * inserisce nel calendario
 *
 * @param <type> $sfide
 * @param <type> $punti
 * @param <type> $giornata
 */
function refresh_calendar($sfide, $punti, $giornata) {

    global $mysql;
    for($i = 0; $i < count($sfide); $i++) {
        $p1 = $punti[$sfide[$i][0]];
        $p2 = $punti[$sfide[$i][1]];
        $g1 = $p1<60 ? 0 : (int)(($p1-60)/6);
        $g2 = $p2<60 ? 0 : (int)(($p2-60)/6);
        if($g1>$g2) {
            $r1='V';
            $r2='S';
        } else if($g2>$g1) {
            $r1='S';
            $r2='V';
        } else if($g1==$g2) {
            $r1='P';
            $r2='P';
        }
        $risultati[] = array($giornata, $sfide[$i][0], $sfide[$i][1], $p1, $p2, $g1, $g2, $r1, $r2);
    }
    $ok = mysql_query("DELETE FROM calendario WHERE giornata=$giornata", $mysql);
    if(!$ok) {
        rollback();
        system_error('Impossibile modificare la giornata corrente nel calendario');
    }
    $first = true;
    $query = 'INSERT INTO calendario(giornata, squadra1, squadra2, punti1, punti2, gol1, gol2, risultato1, risultato2) VALUES';
    foreach($risultati as $r) {
        if (!$first) $query .= ', ';
        $query .= " ($r[0],'$r[1]','$r[2]',$r[3],$r[4],$r[5],$r[6],'$r[7]','$r[8]')";
        $first = false;
    }
    $result = mysql_query($query, $mysql);
    if(!$result) {
        rollback();
        system_error('Impossibile inserire i risultati di giornata nel calendario');
    }
}

/**
 * Marca come giocata la giornata attuale e sposta quindi in avanti il campionato
 *
 * @param <int> $giornata
 */
function next_sportday($giornata) {

    global $mysql;
    $ok = mysql_query("UPDATE giornate SET giocata=TRUE WHERE n=$giornata", $mysql);
    if(!$ok) {
        rollback();
        system_error('Impossibile avanzare di una giornata');
    }
}

/**
 * Calcola il punteggio della squadra considerando tutte le regole
 *
 * @param <string> $team    Nome della squadra
 * @param <array> $form     Struttura dati in forma ruolo->tipo-> (nome)
 */
function calculate_score($team, &$form, &$voti) {

    // Parametri da file di config
    global $numero_sostituzioni, $modificatore_difesa,
           $modificatore_centrocampo, $modificatore_attacco,
           $fattore_campo;

    $MAX_SUBST = $numero_sostituzioni;

    $sum = 0;
    $playerCount = 0;     // giocatori con voto titolari
    $reserveCount = 0;    // giocatori entrati dalla panchina

    // Somma voti titolari
    foreach($form as $role => $subsets)
        foreach($subsets as $type => $players)
            if($type == 'TITOLARE')
                foreach($players as $player)
                    if($voti[$player] && $voti[$player][0] != 0) {
                        $sum += $voti[$player][0] + $voti[$player][1];
                        $playerCount++;
                        // Copia dei voti effettivi (base) in un vettore a parte
                        $voti_effettivi[$role][] = $voti[$player][0];
                    }

    // Sostituzione Portiere
    $por = $form['P']['TITOLARE'][0];
    $por_ris = $form['P']['PRIMA_RISERVA'][0];
    if(!$voti[$por] || $voti[$por][0] == 0)
        if($voti[$por_ris] && $voti[$por_ris][0] != 0 && $playerCount < 11 && $reserveCount < $MAX_SUBST) {
            $sum += $voti[$por_ris][0] + $voti[$por_ris][1];
            $playerCount++;
            $reserveCount++;
            $voti_effettivi['P'][] = $voti[$por_ris][0];
        }
    // Sostituzione Difensori
    $zeros = 0;     // Numero di giocatori da sostituire
    foreach($form['D']['TITOLARE'] as $dif)
        if(!$voti[$dif] || $voti[$dif][0] == 0)
            $zeros ++;
    $r1 = $form['D']['PRIMA_RISERVA'][0];
    $r2 = $form['D']['SECONDA_RISERVA'][0];
    $dif_res = array($r1, $r2);
    foreach($dif_res as $res)
        if($voti[$res] && $voti[$res][0] != 0 && $playerCount < 11 && $reserveCount < $MAX_SUBST && $zeros > 0) {
            $sum += $voti[$res][0] + $voti[$res][1];
            $playerCount ++;
            $reserveCount ++;
            $zeros --;
            $voti_effettivi['D'][] = $voti[$res][0];
        }
    // Sostituzione Centrocampisti
    $zeros = 0;
    foreach($form['C']['TITOLARE'] as $cen)
        if(!$voti[$cen] || $voti[$cen][0] == 0)
            $zeros ++;
    $r1 = $form['C']['PRIMA_RISERVA'][0];
    $r2 = $form['C']['SECONDA_RISERVA'][0];
    $cen_res = array($r1, $r2);
    foreach($cen_res as $res)
        if($voti[$res] && $voti[$res][0] != 0 && $playerCount < 11 && $reserveCount < $MAX_SUBST && $zeros > 0) {
            $sum += $voti[$res][0] + $voti[$res][1];
            $playerCount ++;
            $reserveCount ++;
            $zeros --;
            $voti_effettivi['C'][] = $voti[$res][0];
        }
    // Sostituzione Attaccanti
    $zeros = 0;
    foreach($form['A']['TITOLARE'] as $att)
        if(!$voti[$att] || $voti[$att][0] == 0)
            $zeros ++;
    $r1 = $form['A']['PRIMA_RISERVA'][0];
    $r2 = $form['A']['SECONDA_RISERVA'][0];
    $att_res = array($r1, $r2);
    foreach($att_res as $res)
        if($voti[$res] && $voti[$res][0] != 0 && $playerCount < 11 && $reserveCount < $MAX_SUBST && $zeros > 0) {
            $sum += $voti[$res][0] + $voti[$res][1];
            $playerCount ++;
            $reserveCount ++;
            $zeros --;
            $voti_effettivi['A'][] = $voti[$res][0];
        }
            
    // Calcolo modificatori
    if($modificatore_difesa) $sum += _mod_difesa($voti_effettivi);
    if($modificatore_centrocampo) $sum += _mod_centrocampo($voti_effettivi);
    if($modificatore_attacco) $sum += _mod_attacco($voti_effettivi);
    if($fattore_campo) ; // TODO: vedere se la squadra è in casa o meno
    
    return $sum;
}

/**
 * Calcola il modificatore difesa facendo la media dei 3 migliori difensori e il portiere
 * e convertendo secondo la tabella
 *
 * @param <array<float>> $voti  Voti effettivi (senza bonus/malus) dei giocatori in campo
 */
function _mod_difesa($voti) {
    
    if(count($voti['D']) >= 4 && $voti['P']) {
        rsort($voti['D']);
        $best = array_slice($voti['D'], 0, 3);
        $tot = doubleval(($voti['P'][0] + array_sum($best))/4);
        
        if      ($tot < 6)                $bonus = 0;
        else if ($tot >= 6 && $tot < 6.5) $bonus = 1;
        else if ($tot >= 6.5 && $tot < 7) $bonus = 3;
        else if ($tot >= 7)               $bonus = 6;

        return $bonus;
    } else
        return 0;
}

/**
 * Modificatore Centrocampo
 *
 * @param <type> $cen
 */
function _mod_centrocampo($voti) {
    return 0;
}

/**
 * Modificatore Attacco
 *
 * @param <type> $att
 */
function _mod_attacco($voti) {
    return 0;
}

/* -- MANAGER -- */

/**
 * Cancella il contenuto di tutte le tabelle per predisporle ad un nuovo campionato
 */
function delete_championship() {

    global $mysql;
    start_transaction();
    $ok = mysql_query("DELETE FROM giornate", $mysql);
    $ok = mysql_query("DELETE FROM calendario", $mysql);
    $ok = mysql_query("DELETE FROM voti", $mysql);
    $ok = mysql_query("DELETE FROM squadre", $mysql);
    $ok = mysql_query("DELETE FROM giocatori", $mysql);
    $ok = mysql_query("DELETE FROM formazioni", $mysql);
    $ok = mysql_query("DELETE FROM commenti", $mysql);
    $ok = mysql_query("DELETE FROM utenti WHERE NOT tipo='ADMIN'", $mysql);
    if(!$ok) {
        rollback();
        system_error('Errore nell\'azzeramento del campionato');
    }
    commit();
    close_db();
}

/**
 * Inserisce le giornate del calendario nel database. Se le squadre partecipanti
 * sono in numero tale che le giornate del vero campionato non sono coperte in maniera
 * esatta da un numero intero di gironi, vengono scelte delle giornate "avanzanti"
 * che costituiscono un sotto-torneo a punti
 *
 * @param <type> $n_giornate Numero di giornate totali del campionato
 * @param <type> $torneo     Array delle giornate di mini-torneo
 */
function create_sportdays($n_giornate, $torneo) {
    
    global $mysql;
    $first = true;
    $query = 'INSERT INTO giornate(n, torneo) VALUES';
    for($i=1; $i <= $n_giornate; $i++) {
        $t = false;
        if(!empty($torneo))
            if(in_array($i,$torneo)!=false)
                $t = true;
        if (!$first) $query .= ', ';
        $query .= " (".$i.",".(int)$t.")";
        $first = false;
    }
    $ok = mysql_query($query, $mysql);
    if(!$ok) {
        rollback();
        system_error("Errore nella creazione delle giornate");
    }
}

/**
 * Inserisce nel DB le squadre
 *
 * @param <string array> $team Array di squadre
 */
function create_teams($team) {

    global $mysql;
    $first = true;
    $query = 'INSERT INTO squadre(nome) VALUES';
    for($i = 0; $i < count($team); $i++) {
        $team[$i] = ucwords(strtolower($team[$i]));
        if (!$first) $query .= ', ';
        $query .= " ('$team[$i]')";
        $first = false;
    }
    $newf = mysql_query($query, $mysql);
    if(!$newf) {
        rollback();
        system_error("Errore nella creazione delle squadre");
    }
}

/**
 * Genera il calendario del campionato e del minitorneo e lo inserisce nel DB
 *
 * @param <string array> $team Array delle squadre partecipanti
 * @param <int> $n_giornate    Numero di giornate totali
 * @param <int array> $torneo  Giornate assegnate al mini-torneo
 */
function create_calendar($team, $n_giornate, $torneo) {

    global $mysql;
    $calendario = generate_calendar($team, $n_giornate, $torneo);
    $first = true;
    $query = 'INSERT INTO calendario(giornata, squadra1, squadra2) VALUES';
    for($i=0; $i<count($calendario); $i++) {
        if (!$first) $query .= ', ';
        $query .= " (".$calendario[$i][0].",'".$calendario[$i][1]."','".$calendario[$i][2]."')";
        $first = false;
    }
    $newf = mysql_query($query, $mysql);
    if(!$newf) {
        rollback();
        system_error("Errore nella creazione del calendario");
    }
}

/**
 * Genera il calendario completo di campionato e mini-torneo.
 * Genera un girone singolo con l'algoritmo di Berger e lo replica aggiungendo
 * infine le giornate di mini-torneo.
 * Restituisce una tabella a 3 colonne contenente tutte le sfide di giornata
 *
 * @param <type> $squadre
 * @param <type> $n_giornate
 * @param <type> $torneo
 */
function generate_calendar($squadre, $n_giornate, $torneo) {
    
    $n = count($squadre);
    // Prima giornata
    $giornate[1]=$squadre;
    $fix = $squadre[$n-1];
    // Giornate dalla 2 alla n-1
    for($i = 2; $i <= $n-1; $i++) {
        $prec = $giornate[$i-1];
        array_pop($prec);   // Rimuovo l'ultimo elemento, che andrà mantenuto fisso
        $last = array_pop($prec);   // $prec è stato accorciato di 1
        $new[] = $last;
        for($j = 0; $j < count($prec); $j++) {
            $new[] = $prec[$j];
        }
        $new[] = $fix;  // Riaggiungo l'ultimo elemento fisso
        $giornate[$i]=$new;
        $new = NULL;
    }
    // Conversione in tabelle N/2 x 2
    for($i = 1; $i <= count($giornate); $i++) {
        for($j = 0; $j < $n/2; $j++) {  // riga della tabella
            $table[$i][$j] = array($giornate[$i][$j], $giornate[$i][$n-$j-1]);
        }
    }
    $n_gironi = (int)($n_giornate/($n-1));
    $ind = 0;
    // Singolo girone
    for($i = 1; $i <= $n - 1; $i++) {
        for($j = 0; $j < $n/2; $j++) {
            $girone[] = array($i, $table[$i][$j][0], $table[$i][$j][1]);
        }
    }
    // Concatenamento gironi
    // Tutte le giornate del campionato
    $calendario = $girone;
    for($i = 1; $i <= $n_gironi - 1; $i++) {
        $calendario = array_merge($calendario, $girone);
    }
    // Determino numeri giornate di campionato
    for($i = 1; $i <= $n_giornate; $i++)
        if(empty($torneo) || !in_array($i,$torneo))
            $camp[] = $i;
    // Assegno i numeri di giornata
    $index = 0;
    for($i = 0; $i < count($calendario); $i = $i + ($n/2)) {
        for($j = $i; $j < $i + ($n/2); $j++)
            $calendario[$j][0] = $camp[$index];
        $index++;
    }
    // Aggiungo le giornate del mini-torneo
    if(!empty($torneo)) {
        $singola_g = array_merge(array_slice($girone,0,$n/2));

        $n_torn = count($torneo);
        for($i = 1; $i <= $n_torn; $i++) {
            for($j = 0; $j < $n/2; $j++)
                $singola_g[$j][0] = $torneo[$i-1];
            $calendario = array_merge($calendario, $singola_g);
        }
    }
    return $calendario;
}

/**
 * Punto unico di gestione dei messaggi d'errore
 *
 * @param <string> $message  Messaggio d'errore da visualizzare nella pagina
 */
function system_error($message) {
    global $baseUrl;
    header("location: " . $baseUrl . "engine/error_handler.php?message=".urlencode($message));
}

/**
 * -- GESTIONE DATABASE --
 */

function start_transaction() {
    global $mysql;
    mysql_query("SET AUTOCOMMIT=0", $mysql);
    mysql_query("START TRANSACTION", $mysql);
}

function rollback() {
    global $mysql;
    mysql_query("ROLLBACK", $mysql);
}

function commit() {
    global $mysql;
    mysql_query("COMMIT", $mysql);
}

function close_db() {
    global $mysql;
    mysql_close($mysql);
}

/**
 * ==============================================
 * Funzionalità di gestione dello stream del Wall
 * ==============================================
 */

/*
 * Inserisce un evento nello stream
 */
function stream_event_put($subtype, $giornata = null, $object = null) {

    $user = $_SESSION['username'];
    $timestamp = date('Y-m-d H:i:s', time());
    $type = 'EVENT';
    if(!$giornata)
        $giornata = 'NULL';
    if(!$object)
        $object = 'NULL';

    global $mysql;
    start_transaction();
    $result = mysql_query("INSERT INTO stream (user,timestamp,type,subtype,giornata,object)
        VALUE ('$user','$timestamp','$type','$subtype',$giornata,'$object')", $mysql);

    if($result) {
        commit();
        return true;
    } else {
        rollback();
        system_error("Impossibile inserire l'evento nel sistema");
        return false;
    }
}

/**
 * Inserisce un post (messaggio) nello stream
 * che potrà essere in seguito commentato dagli utenti
 */
function stream_post_put($message) {

    $user = $_SESSION['username'];
    $timestamp = date('Y-m-d H:i:s', time());
    $type = 'POST';

    global $mysql;
    start_transaction();
    $result = mysql_query("INSERT INTO stream (user,timestamp,type,content)
            VALUE ('$user','$timestamp','$type','$message')", $mysql);
    $id = mysql_insert_id($mysql);
    if($result) {
        commit();
        return $id;
    } else {
        rollback();
        system_error("Impossibile inserire il post nel sistema");
        return false;
    }
}

/**
 * Inserisce un commento relativo ad un evento o ad un post
 * nello stream
 */
function stream_comment_put($message, $reference) {

    $user = $_SESSION['username'];
    $timestamp = date('Y-m-d H:i:s', time());
    $type = 'COMMENT';

    global $mysql;
    start_transaction();
    $result = mysql_query("INSERT INTO stream (user,timestamp,type,content,reference)
            VALUE ('$user','$timestamp','$type','$message',$reference)", $mysql);
    $id = mysql_insert_id($mysql);
    if($result) {
        commit();
        return $id;
    } else {
        rollback();
        //system_error("Impossibile inserire il commento nel sistema");
        return false;
    }
}

/**
 * Ottiene un certo numero di post ed eventi del wall con relativi commenti e
 * li organizza in una struttura dati opportuna
 */
function get_stream_content($startitem = 0, $numitems = 25, $datetime = null) {

    global $mysql;

    /*
     * Struttura dati:
     * $wall[0]['item'] --> array
     *         ['related'][0] --> array
     *                    [1]
     *                     .
     *                     .
     * $wall[1]['item'] ...
     */

    if($datetime != null)
        $items_result = mysql_query("SELECT * FROM stream WHERE timestamp > '$datetime' ORDER BY timestamp DESC", $mysql);
    else
        $items_result = mysql_query("SELECT * FROM stream WHERE reference IS NULL ORDER BY timestamp DESC LIMIT $startitem, $numitems", $mysql);

    if($items_result) {
        $i = 0;
        while($row = mysql_fetch_assoc($items_result)) {

            $assoc[$row['id']] = $i;        // associazione ID dell'item a indice nell'array
            $ids[] = $row['id'];            // ID di tutti gli item principali

            // POST ed EVENTI
            switch ($row['type']) {

                case 'POST':
                    $wall[$i]['item'] = array('id' => $row['id'], 'user' => $row['user'], 'timestamp' => $row['timestamp'],
                        'type' => $row['type'], 'content' => $row['content']);
                    break;
                case 'EVENT':
                    $wall[$i]['item'] = array('id' => $row['id'], 'user' => $row['user'], 'timestamp' => $row['timestamp'],
                        'type' => $row['type'], 'subtype' => $row['subtype'],
                        'giornata' => $row['giornata'], 'object' => $row['object']);
                    break;
                case 'COMMENT': // Solo nel caso di aggiornamento basato sulla data
                    if(!array_key_exists($row['reference'], $assoc)) {
                        // Commenti non collegati a post o eventi presenti nel dataset selezionato
                        $wall['sparse'][] = array('id' => $row['id'], 'user' => $row['user'],
                            'timestamp' => $row['timestamp'], 'content' => $row['content'],
                            'type' => $row['type'], 'reference' => $row['reference']);
                    }
                    break;
            }

            $wall[$i]['related'] = null;
            $i++;
        }

        if (isset($ids) && count($ids) > 0) {
            // Cerco tutti i commenti relativi agli item principali
            // e li associo ad essi
            $related_result = mysql_query("SELECT * FROM stream WHERE reference IN (" . implode(',', $ids) . ")", $mysql);
            if ($related_result) {

                while ($row = mysql_fetch_assoc($related_result)) {

                    if(array_key_exists($row['reference'], $assoc)) {

                        // COMMENTI a post o eventi
                        $index = $assoc[$row['reference']];
                        $wall[$index]['related'][] = array('id' => $row['id'], 'user' => $row['user'],
                        'timestamp' => $row['timestamp'], 'content' => $row['content']
                            /* ,'type' => $row['type'], 'reference' => $row['reference'] */);
                    }
                }

                return $wall;
            } else {
                system_error("Impossibile recuperare i commenti del Wall");
                return null;
            }
        } else {
            return null;
        }
    } else {
        system_error("Impossibile recuperare il contenuto del Wall");
        return null;
    }
}

function format_post($post, $users) {

    // id, user, timestamp, content
    global $baseUrl;

    $id = $post['item']['id'];
    $user = $post['item']['user'];
    $timestamp = $post['item']['timestamp'];
    $content = $post['item']['content'];
    $comments = $post['related'];

    // selezione eventuale immagine Facebook altrimenti quella di default
    if($users[$user]['fb_uid'] != '')
        $img = 'http://graph.facebook.com/' . $users[$user]['fb_uid'] . '/picture';
    else
        $img = $baseUrl . 'images/user.png';
    
    $userlink = $baseUrl . "stat_squadra.php?team=" . $users[$user]['squadra'];
    
    $comments_html = '';
    if($comments) {
        foreach($comments as $comment) {
            $comments_html .= format_comment($comment, $users);
        }
    }

    // Solo l'utente che ha inserito l'elemento può cancellarlo
    $delete = '';
    if($user == $_SESSION['username'])
        $delete = '<a class="closepostbutton"></a>';

    $html = '<table class="post wallitem" id="' . $id . '">
            <tr>
                <td class="post_img" style="width: 50px;">
                    <a href="' . $userlink . '"><img src="' . $img . '"/></a>
                </td>
                <td style="width: 460px;">
                    <table>
                        <tr class="post_body" valign="top">
                            <td>
                                <div style="display: table-cell; text-align: justify;">
                                    <a href="' . $userlink . '" class="post_body_user">' . $user . '</a>
                                        ' . $content . '<br/>
                                    <span class="post_time">' . $timestamp . '</span><!-- &middot; <span class="post_comment_link">Commenta</span>-->
                                </div>
                            </td>
                        </tr>
                        <!--<tr><td><div class="post_comments">' . $comments_html . '</div></td></tr>-->
                        <!--' . format_commentarea($id) . '-->
                    </table>
                </td>
                <td valign="top">' . $delete . '</td>
            </tr>
         </table>';

    return $html;
}

function format_event($event, $users) {

    global $baseUrl;
    
    $id = $event['item']['id'];
    $user = $event['item']['user'];
    $timestamp = $event['item']['timestamp'];
    $subtype = $event['item']['subtype'];
    $giornata = $event['item']['giornata'];
    $object = $event['item']['object'];
    $comments = $event['related'];
    
    // selezione icona evento
    $img = $baseUrl . 'images/events/' . strtolower($subtype) . '.png';

    // link squadra utente
    $userlink = $baseUrl . "stat_squadra.php?team=" . $users[$user]['squadra'];

    // link giornata
    $last_gg = get_last_sportday();
    if($giornata > $last_gg)
        $gglink = $baseUrl . "form_giornata.php";
    else
        $gglink = $baseUrl . "archivio_formazioni.php?n=" . $giornata;

    // Link a utente 'object'
    $objlink = '';
    if($object)
        $objlink = ' di <a class="event_link" href="' . $baseUrl . "stat_squadra.php?team=" . $users[$object]['squadra'] . '">' . $object . '</a>';

    $comments_html = '';
    if($comments) {
        foreach($comments as $comment) {
            $comments_html .= format_comment($comment, $users);
        }
    }

    switch($subtype) {
        case 'FORM_INS':
            $text = ' ha inserito la formazione' . $objlink . ' per la <a class="event_link" href="' . $gglink . '">' . $giornata . '^ giornata</a>';
            break;
        case 'FORM_MOD':
            $text = ' ha modificato la formazione' . $objlink . ' per la <a class="event_link" href="' . $gglink . '">' . $giornata . '^ giornata</a>';
            break;
        case 'RESULTS_PUB':
            $text = ' ha pubblicato i risultati della <a class="event_link" href="' . $gglink . '">' . $giornata . '^ giornata</a>';
            break;
        case 'PLAYERS':
            $text = ' ha aggiornato l\'elenco dei giocatori di Serie A';
            break;
        case 'TEAM_CREATE':
            $text = ' ha inserito la rosa' . $objlink;
            break;
        case 'TEAM_MOD':
            $text = ' ha modificato la rosa' . $objlink;
            break;
    }

    if($comments_html == '') {
//        $offset_1 = 'style="padding-top: 8px;"';
//        $offset_2 = 'style="margin-top: -4px;"';
    }

    $html = '<table class="event wallitem" id="' . $id . '">
            <tr>' .
                '<td class="event_empty">
                    &nbsp;
                </td>' .
                '<td style="width: 460px;">
                    <table>
                        <tr class="event_body" valign="top">
                            <td>
                                <div style="display: table-cell;">
                                    <img src="' . $img . '" alt="Evento"/>
                                    <a href="' . $userlink . '" class="event_body_user">' . $user . '</a>
                                        ' . $text . ' &middot; 
                                    <span class="post_time">' . $timestamp . '</span><!-- &middot; <span class="post_comment_link">Commenta</span>-->
                                </div>
                            </td>
                        </tr>' . 
                        '<!--<tr><td><div class="post_comments" ' . $offset_1 . '>' . $comments_html . '</div></td></tr>-->
                        <!--' . format_commentarea($id) . '-->
                    </table>
                </td>
            </tr>
         </table>';

    return $html;
}

function format_comment($comment, $users, $hidden = null) {

    global $baseUrl;

    $id = $comment['id'];
    $user = $comment['user'];
    $timestamp = $comment['timestamp'];
    $content = $comment['content'];

    // selezione eventuale immagine Facebook altrimenti quella di default
    if($users[$user]['fb_uid'] != '')
        $img = 'http://graph.facebook.com/' . $users[$user]['fb_uid'] . '/picture';
    else
        $img = $baseUrl . 'images/user.png';
    // link squadra utente
    $userlink = $baseUrl . "stat_squadra.php?team=" . $users[$user]['squadra'];

    if($hidden == true)
        $style = 'style="display: none;"';

    // Solo l'utente che ha inserito l'elemento può cancellarlo
    $delete = '';
    if($user == $_SESSION['username'])
        $delete = '<a class="closebutton"></a>';

    $html = '<table class="comment" ' . $style . ' id="' . $id . '">
                <tr>
                    <td class="comment_img">
                        <a href="' . $userlink . '"><img ' .$style . ' src="' . $img . '"/></a>
                    </td>
                    <td class="comment_body" valign="top">
                        <div style="text-align: justify;">
                            <a href="' . $userlink . '" class="comment_body_user">' . $user . '</a>
                            ' . $content . '<br/>
                            <span class="comment_time">' . $timestamp . '</span>
                        </div>
                    </td>
                    <td valign="top">' . $delete . '</td>
                </tr>
             </table>';

    return $html;
}

function format_commentarea($id) {

    $html = '<tr>
                <td>
                    <div class="insert_comment">
                        <textarea rows="1" wrap="hard" class="commentarea" type="text"
                            name="addcomment_' . $id . '" id="addcomment_' . $id . '">Scrivi un commento...</textarea>

                        <input type="button" name="Commenta" value="Commenta" 
                            style="display: none; float: right; margin-bottom: 4px; margin-right: 5px;" class="commentbutton"/>
                        <div style="clear: both;"></div>
                    </div>
                </td>
            </tr>';

    return $html;
}

/**
 * Usa la struttura dati prodotta dal metodo get_stream_content e la formatta in HTML
 */
function format_stream_content($wall) {

    global $mysql;

    //array associativo squadre-(presidenti,fb_uid) x link vari
    $result = mysql_query("SELECT s.nome,u.username,u.fb_uid FROM squadre as s join utenti as u on s.presidente = u.username", $mysql);
    while($row = mysql_fetch_assoc($result)) {
        $users[$row['username']] = array('squadra' => $row['nome'], 'fb_uid' => $row['fb_uid']);
    }

    $html = '';

    for($i = 0; $i < count($wall); $i++) {

        switch($wall[$i]['item']['type']) {
            case 'POST':
                $html .= format_post($wall[$i], $users);
            break;
            case 'EVENT':
                $html .= format_event($wall[$i], $users);
            break;
        }
    }
    return $html;
}

/**
 * Funzione che genera l'html dei nuovi post e quello, separato, dei commenti
 * da aggiungere ai vecchi post
 * TODO: Considera l'html intero da aggiungere e i commenti sparsi!!
 */
function format_new_stream_content($wall) {

    global $mysql;

    //array associativo squadre-(presidenti,fb_uid) x link vari
    $result = mysql_query("SELECT s.nome,u.username,u.fb_uid FROM squadre as s join utenti as u on s.presidente = u.username", $mysql);
    while($row = mysql_fetch_assoc($result)) {
        $users[$row['username']] = array('squadra' => $row['nome'], 'fb_uid' => $row['fb_uid']);
    }

    $html = '';

    for($i = 0; $i < count($wall); $i++) {

        switch($wall[$i]['item']['type']) {
            case 'POST':
                $html .= format_post($wall[$i], $users);
            break;
            case 'EVENT':
                $html .= format_event($wall[$i], $users);
            break;
        }
    }
    $data['html'] = $html;

    if ($wall['sparse']) {
        foreach ($wall['sparse'] as $item) {
            $temp_html = format_comment($item, $users);
            $data['comments'][$item['reference']] = $temp_html;
        }
    }

    $data['timestamp'] = date('Y-m-d H:i:s', time());

    return $data;

}
?>