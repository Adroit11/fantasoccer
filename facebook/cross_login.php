<?php

include_once(dirname(dirname(__FILE__)) . "/engine/engine.php");

$uid = $_GET['uid'];

$fbsession = get_fb_session();

if ($uid) {

    if ($uid == $fbsession['uid']) {

        $user = get_user_by_uid($uid);

        if ($user) {  // UID associato ad un utente

            $_SESSION['username'] = $user[username];
            $_SESSION['tipo'] = $user[tipo];
            $_SESSION['fb_uid'] = $uid;

            echo "OK";
        } else {    // Login fallito
            echo "ERRORE - ID Facebook non associato a nessun utente";
        }
    } else {

        echo "ERRORE - Sei loggato ad un account Facebook non corrispondente";
    }

    $result = mysql_query("SELECT * FROM utenti WHERE fb_uid='$uid'");
} else {
    echo "ERROR - UID non valido o assente";
}
?>
