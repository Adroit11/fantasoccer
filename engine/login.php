<?php

include_once dirname(__FILE__) . "/engine.php";
//include_once( "engine.php" );

if (!empty($_GET[action]) && $_GET[action] == 'logout') {

    unset($_SESSION['username']);
    unset($_SESSION['tipo']);
    unset($_SESSION['fb_uid']);
    $_SESSION = array();

    header("location: {$baseUrl}index.php");
} else {

    $username = $_POST['username'];
    $pwd = md5($_POST['pwd']);
    $referrer = $_POST['referrer'];

    $type = $_POST['logintype'];

    // Login classico
    $result = mysql_query("SELECT * FROM utenti WHERE username='" . $username . "' AND pwd='" . $pwd . "'", $mysql);

    if (mysql_num_rows($result) != 0) {  // Login corretto
        
        $user = mysql_fetch_assoc($result);

        if ($type == 'facebook') {

            // associazione dello UID Facebook al profilo utente (se non vi sono errori)
            $uid = $_POST['facebook_uid'];

            $temp_user = get_user_by_uid($uid);
            // Già usato da utenti diversi da se stesso
            if($temp_user != null && $temp_user['username'] != $username) {
                system_error("ERRORE: Account Facebook già in uso da un altro utente");
                exit;
            }
            // Ho già un altro UID associato
            else if ($user['fb_uid'] != null && $user['fb_uid'] != $uid) {
                system_error("ERRORE: Sei già associato ad un altro account Facebook");
                exit;
            } else {
                // Inserisco l'UID nel database associandolo all'utente
                $result = mysql_query("UPDATE utenti SET fb_uid='$uid' WHERE username='$username'");
                $user[fbuid] = $uid;
                get_fb_session();
            }
        }

        $result = mysql_query("SELECT fb_uid FROM utenti WHERE username='$username'");
        $result = mysql_fetch_assoc($result);

        $_SESSION['username'] = $user['username'];
        $_SESSION['tipo'] = $user['tipo'];
        $_SESSION['fb_uid'] = $result['fb_uid'];

        header("location: $referrer");
    } else {    // Login fallito
        header("location: {$baseUrl}index.php?login=failed");
    }

    close_db();
}
?>
