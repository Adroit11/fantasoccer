<?php

include_once dirname(__FILE__) . "/engine.php";

// Parsing della tabella dell'elenco giocatori
function parse_players_table($page) {
    
    $players = array();
    $begin = strpos($page,"<table>")+strlen("<table>");
    $end = strpos($page, "\n", $begin);
    $page = substr($page, $begin, $end - $begin);
    $rows = explode("<tr>",$page);
    unset($page);
    $rows = array_slice(array_merge($rows, array()), 3);
    for($i=0; $i<count($rows); $i++) {
        $fields = explode("<td align=center height=20>", $rows[$i]);
        $nome = ucwords(strtolower(trim(substr($fields[1], 0, strpos($fields[1], "<")))));
        if(strpos($nome,"'")) {
            $nome = str_replace("'","",$nome);  // Rimozione apici che causano stringhe di query malformate
        }
        if(count($players) > 450) {
            $abc=0;
            $abc++;
        }
        $ruolo = ucwords(strtolower(trim(substr($fields[2], 0, strpos($fields[2], "<")))));
        $seriea = ucwords(strtolower(trim(substr($fields[3], 0, strpos($fields[3], "<")))));
        $players[] = array($nome, $ruolo, $seriea);
    }
    return $players;
}

// WARNING: Il documento HTML d'origine è malformato, quindi per ora non funziona
function parse_players_table_dom($html) {

    // La presenza di questi tag fa fallire il parsing - documento html non valido
    $html = str_replace("<font color=#FFFFFF face=Verdana size=3>", "", $html);
    $html = str_replace("</font>", "", $html);
    $html = str_replace(" align=center height=20", "", $html);

    $dom = new DOMDocument();
    $dom->loadHTML($html);
    $dom->preserveWhiteSpace = false;
    $dom->strictErrorChecking = false;
    $table = $dom->getElementsByTagName("table");
    $table = $table->item(0);
    $rows  = $table->getElementsByTagName("tr");

    for($i = 3; $i < $rows->length; $i++) {
        $row = $rows->item($i);

        $cols = $row->getElementsByTagName("td");
        $nome = str_replace("'", "", ucwords(strtolower(trim($cols->item(1)->nodeValue))));
        $ruolo = ucwords(strtolower(trim($cols->item(2))));
        $seriea = ucwords(strtolower(trim($cols->item(3)->nodeValue)));

        $players[] = array($nome, $ruolo, $seriea);
    }

    return $players;
}

// Parsing della tabella dei voti di giornata

function parse_votes_table($html) {
    
    // La presenza di questi tag fa fallire il parsing - documento html non valido
    $html = str_replace("<font color=#FFFFFF face=Verdana size=3>", "", $html);
    $html = str_replace("</font>", "", $html);

    $dom = new DOMDocument();
    $dom->loadHTML($html);
    $dom->preserveWhiteSpace = false;
    $dom->strictErrorChecking = false;
    $table = $dom->getElementsByTagName("table");
    $table = $table->item(0);
    $rows  = $table->getElementsByTagName("tr");

    for($i = 3; $i < $rows->length; $i++) {
        $row = $rows->item($i);

        $cols = $row->getElementsByTagName("td");
        
        $nome = str_replace("'", "", ucwords(strtolower(trim($cols->item(1)->nodeValue))));
        $seriea = ucwords(strtolower(trim($cols->item(3)->nodeValue)));

        $votoG      = str_replace(",", ".", trim($cols->item(4)->nodeValue));
        $votoBM_G   = str_replace(",", ".", trim($cols->item(30)->nodeValue));
        $votoCdS    = str_replace(",", ".", trim($cols->item(9)->nodeValue));
        $votoBM_CdS = str_replace(",", ".", trim($cols->item(31)->nodeValue));
        $votoTS     = str_replace(",", ".", trim($cols->item(14)->nodeValue));
        $votoBM_TS  = str_replace(",", ".", trim($cols->item(32)->nodeValue));

        // Bug Fix:
        $votoG = is_numeric($votoG) ? $votoG : 0;
        $votoBM_G = is_numeric($votoBM_G) ? $votoBM_G : 0;
        $votoCdS = is_numeric($votoCdS) ? $votoCdS : 0;
        $votoBM_CdS = is_numeric($votoBM_CdS) ? $votoBM_CdS : 0;
        $votoTS = is_numeric($votoTS) ? $votoTS : 0;
        $votoBM_TS = is_numeric($votoBM_TS) ? $votoBM_TS : 0;

        // Validazione e definizione del voto
        // Priorità: 1-Gazzetta, 2-CorriereDelloSport, 3-TuttoSport
        if($votoBM_G != 0)  {
            if($votoG == 0) $votoG = $votoBM_G;
            $voto = $votoG;
            $votoBm = $votoBM_G;
            $bm     = $votoBm - $voto;
        } else if($votoBM_CdS != 0) {
            if($votoCdS == 0) $votoCdS = $votoBM_CdS;
            $voto = $votoCdS;
            $votoBm = $votoBM_CdS;
            $bm     = $votoBm - $voto;
        } else if($votoBM_TS != 0) {
            if($votoTS == 0) $votoTS = $votoBM_TS;
            $voto = $votoTS;
            $votoBm = $votoBM_TS;
            $bm     = $votoBm - $voto;
        } else {
            $voto = 0;
            $bm   = 0;
        }

        
//        if(!is_numeric($votoG) && $votoBM_G == 0) {
//            if(is_numeric($votoCdS) || $votoBM_CdS != 0) {
//                $votoBm = is_numeric($votoBM_CdS) ? $votoBM_CdS : 0;
//                $voto   = is_numeric($votoCdS) ? $votoCdS : $votoBm;
//                $bm     = $votoBm - $voto;
//            } else if(is_numeric($votoTS) || $votoBM_TS != 0) {
//                $votoBm = is_numeric($votoBM_TS) ? $votoBM_TS : 0;
//                $voto   = is_numeric($votoTS) ? $votoTS : $votoBm;
//                $bm     = $votoBm - $voto;
//            } else {
//                $voto = 0;
//                $bm   = 0;
//            }
//        } else {
//            $votoBm = is_numeric($votoBM_G) ? $votoBM_G : 0;
//            $voto   = is_numeric($votoG) ? $votoG :  $votoBm;
//            $bm     = $votoBm - $voto;
//        }

        $gf      = is_numeric(trim($cols->item(5)->nodeValue)) ? trim($cols->item(5)->nodeValue) : 0;
        $gs      = is_numeric(trim($cols->item(6)->nodeValue)) ? trim($cols->item(6)->nodeValue) : 0;
        $autogol = is_numeric(trim($cols->item(7)->nodeValue)) ? trim($cols->item(7)->nodeValue) : 0;
        $gdv     = is_numeric(trim($cols->item(23)->nodeValue)) ? trim($cols->item(23)->nodeValue) : 0;
        $gdp     = is_numeric(trim($cols->item(24)->nodeValue)) ? trim($cols->item(24)->nodeValue) : 0;
        $amm     = is_numeric(trim($cols->item(21)->nodeValue)) ? trim($cols->item(21)->nodeValue) : 0;
        $esp     = is_numeric(trim($cols->item(22)->nodeValue)) ? trim($cols->item(22)->nodeValue) : 0;
        $assist  = is_numeric(trim($cols->item(8)->nodeValue)) ? trim($cols->item(8)->nodeValue) : 0;
        $rigsba  = is_numeric(trim($cols->item(25)->nodeValue)) ? trim($cols->item(25)->nodeValue) : 0;
        $rigpar  = is_numeric(trim($cols->item(26)->nodeValue)) ? trim($cols->item(26)->nodeValue) : 0;
        $tit     = is_numeric(trim($cols->item(29)->nodeValue)) ? trim($cols->item(29)->nodeValue) : 0;
        
        if( ! ($voto == 0 && $bm == 0) )
            $players[$nome] = array($voto,$bm,$gs,$gf,$autogol,$gdv,$gdp,$amm,$esp,$assist,$rigsba,$rigpar,$tit);
    }

    return $players;
}

/**
 * Verifica su PianetaFantacalcio.it la disponibilità dei voti per la giornata richiesta
 *
 * @param <int> $giornata
 */
function verify_vote_availability($giornata) {
    
    global $url_checkvotes;
    
    // <a href="Voti_Ufficiali.asp" class="link" title="Voti Squadre">Voti Ufficiali 2</a>
    $voti_catalogue = http_get_file($url_checkvotes);
    // Link da cercare
    $search = '<a href="Voti_Ufficiali.asp" class="link" title="Voti Squadre">Voti Ufficiali ' . $giornata . '</a>';
    if(!strpos($voti_catalogue, $search)) {   // I voti non sono disponibili
        //rollback();
        system_error("I voti della giornata richiesta non sono ancora disponibili");
        return false;
    } else {
        unset($voti_catalogue);
        return true;
    }
}

/**
 * Verifica su PianetaFantacalcio.it la disponibilità dei voti per la giornata richiesta
 * IN BACKGROUND
 *
 * @param <int> $giornata
 */
function verify_vote_availability_bg($giornata) {
    
    global $url_checkvotes;
    
    echo "<br/>LINK DA SCARICARE:<br/><br/>" . $url_checkvotes . "<br/><br/>";
    
    // <a href="Voti_Ufficiali.asp" class="link" title="Voti Squadre">Voti Ufficiali 2</a>
    $voti_catalogue = http_get_file($url_checkvotes);
    
    // Link da cercare
    $search = '<a href="Voti_Ufficiali.asp" class="link" title="Voti Squadre">Voti Ufficiali ' . $giornata . '</a>';
    
    echo "Link da cercare: " . $search . "<br/>";
    
    if(!strpos($voti_catalogue, $search))
    {   // I voti non sono disponibili
        return false;
    }
    else
    {
        unset($voti_catalogue);
        return true;
    }
}

/**
 * Scarica i voti di giornata da PianetaFantacalcio, ne fa il parsing e li inserisce nel database
 *
 * @param <type> $giornata
 */
function download_votes($giornata) {

    global $mysql, $url_downvotes;
    
    $voti_file = http_get_file($url_downvotes . $giornata);
    if(!$voti_file) {
        rollback();
        system_error("Impossibile scaricare la lista voti da PianetaFantacalcio");
    }
    $voti = parse_votes_table($voti_file);
    $first = true;
    $query = 'INSERT INTO voti(giornata,nome,voto,bonusmalus,gs,gf,autogol,gdv,gdp,ammonizioni,espulsioni,assist,rigsba,rigpar,titolare) VALUES';
    foreach($voti as $n=>$v) {
        if (!$first) $query .= ',';
        $query .= " ($giornata,'$n',$v[0],$v[1],$v[2],$v[3],$v[4],$v[5],$v[6],$v[7],$v[8],$v[9],$v[10],$v[11],$v[12])";
        $first = false;
    }
    unset($voti_file);
    $newf = mysql_query($query, $mysql);
    if(!$newf) {
        rollback();
        system_error("Errore nell'inserimento sul database dei nuovi voti");
    }
    return $voti;
}

/**
 * Scarica la lista dei giocatori da PianetaFantacalcio e la inserisce nel DB
 */
function download_players_list() {

    global $mysql, $url_playerlist;
    $players_file = http_get_file($url_playerlist);
    if(!$players_file)
        system_error("Impossibile scaricare l'elenco dei giocatori");
    $players = parse_players_table($players_file);
    unset($players_file);

    $first = true;
    $query = 'INSERT INTO giocatori(nome, ruolo, seriea) VALUES';
    for($i = 0; $i < count($players); $i++) {
        if (!$first) $query .= ', ';
        $query .= " ('".$players[$i][0]."','".$players[$i][1]."','".$players[$i][2]."')";
        $first = false;
    }
    //echo $query;
    $newf = mysql_query($query, $mysql);
    if(!$newf) {
        rollback();
        system_error("Errore nell'inserimento dell'elenco giocatori");
    }
}

// Download pagina web o file con cURL
function http_get_file($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $contents = curl_exec ($ch);
    curl_close ($ch);
    return $contents;
}

?>
