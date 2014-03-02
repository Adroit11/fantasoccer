<?php

/**
 * Contiene le funzioni utilizzate dal sito per l'interfacciamento
 * ai servizi di Facebook
 */

require_once( "facebook.php" );

/**
 * Ottiene l'istanza dell'oggetto di connessione a Facebook
 */
function get_fb_instance() {

    global $fb_appid, $fb_secret;

    //if (!$_SESSION['fb_instance']) {
    
        Facebook::$CURL_OPTS[CURLOPT_CAINFO] = 'D:\Workspace\xampplite\htdocs\FantaProject\facebook\fb_ca_chain_bundle.crt';
        Facebook::$CURL_OPTS[CURLOPT_SSL_VERIFYPEER] = false;
        Facebook::$CURL_OPTS[CURLOPT_SSL_VERIFYHOST] = 2;

        $tmp_fb = new Facebook(array(
                    'appId' => $fb_appid,
                    'secret' => $fb_secret,
                    'cookie' => true,
                ));
        $_SESSION['fb_instance'] = $tmp_fb;

    //}

    return $_SESSION['fb_instance'];
}

/**
 * Ottiene la sessione che l'utente ha instaurato con Facebook.
 *
 * @return Facebook session object. Null se l'utente non è loggato a Facebook.
 */
function get_fb_session() {
    
    $tmp_fb = get_fb_instance();
    $tmp_sess = $tmp_fb->getSession();
    
    $_SESSION['fb_session'] = $tmp_sess;

    return $_SESSION['fb_session'];
}

function uid_used($uid) {

    global $mysql;
    if($uid != null && $uid != "") {
        $result = mysql_query("SELECT * FROM utenti WHERE fb_uid='$uid'", $mysql);
        if(mysql_num_rows($result) != 0) {
            return true;
        } else {
            return false;
        }
        if(!$result)
            system_error('Errore nell\'ottenere informazioni sulla giornata');
    } else {
        return false;
    }
}

function logged_user_uid() {

    global $mysql;
    $result = mysql_query("SELECT fb_uid FROM utenti WHERE username='{$_SESSION['username']}'");
    if($result) {
        $result = mysql_fetch_assoc($result);
        return $result['fb_uid'];
    } else {
        system_error('Errore nell\'ottenere l\'UID dell\'utente loggato');
        return null;
    }
}

function get_user_by_uid($uid) {

    global $mysql;
    if($uid != null && $uid != "") {
        $result = mysql_query("SELECT * FROM utenti WHERE fb_uid='$uid'", $mysql);
        if (mysql_num_rows($result) != 0) {
            
            $user = mysql_fetch_assoc($result);
            return $user;

        } else
            return null;
    } else {
        return null;
    }
}

/**
 * Notifica agli utenti abilitati Facebook l'inserimento di un post sull wall
 * di NewFantaTorneo
 */
function notifica_post_facebook() {

    global $baseUrl, $mysql;

    $uname = $_SESSION['username'];
    $facebook = get_fb_instance();

    $utenti = mysql_query("SELECT fb_uid FROM utenti WHERE fb_uid IS NOT NULL", $mysql);
    if($utenti)
        while($row = mysql_fetch_assoc($utenti))
            $uids[] = $row['fb_uid'];
    //$uid_string = implode(',', $uids);

    $attachment = array(
        'access_token' => $facebook->getAccessToken(),
        'name' => 'NewFantaTorneo',
        'link' => "$baseUrl",
        'caption' => "Nuovo post di " . $uname . " sul wall",
        'description' => 'Corri a leggerlo!',
        'picture' => $baseUrl . 'images/facebook_post_image_message.png',
        'message' => 'Hai un nuovo messaggio in bacheca!'
    );

    foreach($uids as $uid)
        if($uid != $_SESSION['fb_uid'])
            @$facebook->api("/" . $uid . "/feed", 'POST', $attachment);
}

/**
 * Pubblica il risultato della partita sul Wall dell'utente
 *
 */
function publish_result($giornata, $message, $description, $image, $uids, $uid = 'me') {

    global $baseUrl;
    $facebook = get_fb_instance();

    $uids = implode(',', $uids);

    $attachment = array(
        'access_token' => $facebook->getAccessToken(),
        'name' => 'NewFantaTorneo',                         // Titolo
        'link' => "$baseUrl",                               // Link del titolo
        'caption' => "Risultati $giornata^ giornata",       // Appare sotto il titolo
        'description' => $description,                      // Testo semplice nel corpo del post (Squadra A  1 : 0  Squadra B)
        'picture' => $baseUrl . $image,                     // immagine visualizzata
        'message' => $message                              // 'Hai vinto!', 'Hai Perso' o 'Peccato, un pareggio!'
//        'privacy' => array('value' => 'CUSTOM',             // Rende le notifiche visibili solo agli utenti di NewFantaTorneo
//                           'friends' => 'SOME_FRIENDS',
//                           'allow' => $uids)
    );

    $facebook->api("/$uid/feed", 'POST', $attachment);
}

function notifica_risultati_facebook($sfide, $punti, $giornata) {

    // $p punti
    // $g gol
    // $r risultati
    
    global $mysql;

    $uids = mysql_query("SELECT fb_uid FROM utenti WHERE fb_uid IS NOT NULL");
    $uids = mysql_fetch_array($uids, MYSQL_NUM);

    for ($i = 0; $i < count($sfide); $i++) {
        $p[0] = $punti[$sfide[$i][0]];
        $p[1] = $punti[$sfide[$i][1]];
        $g[0] = $p[0] < 60 ? 0 : (int) (($p[0] - 60) / 6);
        $g[1] = $p[1] < 60 ? 0 : (int) (($p[1] - 60) / 6);
        if ($g[0] > $g[1]) {
            $r[0] = 'V';
            $r[1] = 'S';
        } else if ($g[1] > $g[0]) {
            $r[0] = 'S';
            $r[1] = 'V';
        } else if ($g[1] == $g[0]) {
            $r[0] = 'P';
            $r[1] = 'P';
        }
        $result[0] = mysql_query("SELECT fb_uid FROM utenti WHERE username=(SELECT presidente FROM squadre WHERE nome='{$sfide[$i][0]}')");
        $result[1] = mysql_query("SELECT fb_uid FROM utenti WHERE username=(SELECT presidente FROM squadre WHERE nome='{$sfide[$i][1]}')");
        for($j = 0; $j < 2; $j++) {
            $uid = mysql_fetch_row($result[$j]);
            $uid = $uid[0];
            if($uid) {
                switch($r[$j]) {
                    case 'V':
                        $message = 'Grande!!! Vittoria!! :-)';
                        $image = 'images/facebook_post_image_win.png';
                    break;
                    case 'P':
                        $message = 'Uff...un inutile pareggio :-|';
                        $image = 'images/facebook_post_image_draw.png';
                    break;
                    case 'S':
                        $message = 'Maledizione....ho perso :-(';
                        $image = 'images/facebook_post_image_lose.png';
                    break;
                }
                $description = $sfide[$i][0] . "  " . $g[0] . " : " . $g[1] . "  " . $sfide[$i][1];
                publish_result($giornata, $message, $description, $image, $uids, $uid);
            }
        }
    }
    
}


?>
