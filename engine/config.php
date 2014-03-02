<?php
/* 
 * Parametri di configurazione globali del sito
 */

/* Configurazione test locale */
$host = "127.0.0.1";
$dbuname = "test";
$dbpwd = "test";
$database = "fantatorneo";

$baseUrl = "http://localhost/newfantatorneo/";
$timeZoneOffset = 0;

/* Configurazione Altervista */
/*$host = "localhost";
$dbuname = "newfantatorneo";
$dbpwd = "rammovandu64";
$database = "my_newfantatorneo";

$baseUrl = "http://newfantatorneo.altervista.org/old/";
$timeZoneOffset = 0;*/

/* Configurazione Hostinger */
/*$host = "mysql.hostinger.it";
$dbuname = "u527023869_fanta";
$dbpwd = "andrea540850";
$database = "u527023869_fanta";

$baseUrl = "http://fantatorneo.zz.mu/";
$timeZoneOffset = 5;*/

$modificatore_difesa = true;
$modificatore_centrocampo = false;
$modificatore_attacco = false;
$numero_sostituzioni = 7;       // 1,2,..,n | 7 equivale a illimitate
$fattore_campo = false;

// Facebook application private data
$fb_appid = "162973473729387";
$fb_apikey = "b63517097a816ed85d3af93c792f58fd";
$fb_secret = "08643ca54e147572ef3d253863bb8a8f";

// URL PianetaFantacalcio

$url_playerlist = "http://www.pianetafantacalcio.it/Giocatori_QuotazioniExcel.asp?giornata=22";
$url_checkvotes = "http://www.pianetafantacalcio.it/fantacalcio.asp";
$url_downvotes = "http://www.pianetafantacalcio.it/Voti_UfficialiTuttiExcel.asp?giornataScelta=";

// URL probabili formazioni
$url_probform_gazzetta      = "http://www.gazzetta.it/Calcio/prob_form/";
$url_probform_fantagazzetta = "http://www.fantagazzetta.com/probabili-formazioni-serie-A";

$firstDay = 3;
?>
