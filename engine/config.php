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

$modificatore_difesa = true;
$modificatore_centrocampo = false;
$modificatore_attacco = false;
$numero_sostituzioni = 7;       // 1,2,..,n | 7 equivale a illimitate
$fattore_campo = false;

// Facebook application private data
$fb_appid = "";
$fb_apikey = "";
$fb_secret = "";

// URL PianetaFantacalcio

$url_playerlist = "http://www.pianetafantacalcio.it/Giocatori_QuotazioniExcel.asp?giornata=22";
$url_checkvotes = "http://www.pianetafantacalcio.it/fantacalcio.asp";
$url_downvotes = "http://www.pianetafantacalcio.it/Voti_UfficialiTuttiExcel.asp?giornataScelta=";

// URL probabili formazioni
$url_probform_gazzetta      = "http://www.gazzetta.it/Calcio/prob_form/";
$url_probform_fantagazzetta = "http://www.fantagazzetta.com/probabili-formazioni-serie-A";

$firstDay = 3;
?>
