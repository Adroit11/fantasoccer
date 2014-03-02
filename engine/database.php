<?php

/**
 * Gestione della connessione al Database MySql
 * 
 * @uses parametri di connessione in config.php
 */

if( !$_SESSION['db_link']) {
    $mysql = mysql_connect($host, $dbuname, $dbpwd);
    $_SESSION['db_link'] = $mysql;
} else {
    $mysql = $_SESSION['db_link'];
}

mysql_select_db($database, $mysql);

?>
