<?php

include_once dirname(__FILE__) . "/engine.php";

error_reporting(E_ALL);

$url = "www.pianetafantacalcio.it/fantacalcio.asp";

echo "URL to download: " . $url . "<br/>";

$ch = curl_init();

echo "Initializing cURL...<br/>";
var_dump($ch);
echo "done.<br/>";

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

echo "Execution...<br/>";
$contents = curl_exec ($ch);

echo "Content:<br/><br/>";

echo $contents;

curl_close ($ch);

echo "<br/>Closed<br/>";

?>
