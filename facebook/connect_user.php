<?php

include_once(dirname(dirname(__FILE__)) . "/engine/engine.php");

gatekeeper();

// Aggiungi il Facebook UID all'utente

$uid = $_GET['uid'];

if($uid) {
    
    if (!uid_used($uid) || $uid == $_SESSION['fb_uid']) {
        $logged_uid = logged_user_uid();
        if ($logged_uid == null || $logged_uid == '') {
            $result = mysql_query("UPDATE utenti SET fb_uid='$uid' WHERE username='{$_SESSION['username']}'");
            if ($result) {
                $_SESSION['fb_uid'] = $uid;
                get_fb_session();
                echo "OK";
            }
        } else {
            echo "ERRORE - hai già un profilo Facebook associato";
        }
    } else {
        echo "ERRORE - account Facebook già associato ad un altro utente";
    }

    
} else {
    echo "ERRORE - null uid parameter";
}

?>
