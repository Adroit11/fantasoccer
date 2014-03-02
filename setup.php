<?php

include_once dirname(__FILE__) . "/engine/engine.php";
//include_once( "engine/engine.php" );

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<!--
Design by Free CSS Templates
http://www.freecsstemplates.org
Released for free under a Creative Commons Attribution 2.5 License

Site Engineering by Andrewww
-->

<html xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>FantaTorneo *New Generation* - Setup</title>
    <? @include("page_elements/scripts.php"); ?>
    <link rel="shortcut icon" href="pics/favicon.ico"/>
</head>
<body>
<script type="text/javascript">
    function validate() {
        var user = document.getElementById("username").value;
        var pwd1 = document.getElementById("pwd1").value;
        var pwd2 = document.getElementById("pwd2").value;
        var dbhost = document.getElementById("dbhost").value;
        var dbuser = document.getElementById("dbuser").value;
        var dbp1 = document.getElementById("dbpwd1").value;
        var dbp2 = document.getElementById("dbpwd2").value;
        var dbname = document.getElementById("dbname").value;
        if(!(pwd1=="" || pwd2=="") && pwd1==pwd2 && user!="" && dbhost!="" && dbuser!="" && dbp1==dbp2 && !(dbp1=="" || dbp2=="") && dbname!="")
            return true;
        else {
            alert("Inserisci tutti i campi e le password");
            return false;
        }
    }
</script>
<? @include("page_elements/header.php"); ?>
<div id="content">
	<div id="colOne">
		<div id="logo"></div>
	</div>
	<div id="colTwo">
        <div>
            <?php
            if($mysql) {
                $result = mysql_query("SELECT * FROM utenti WHERE tipo='ADMIN'", $mysql);
            }
            if($mysql && mysql_num_rows($result)!=0) { ?>
                <h2>Richiesta non valida</h2>
                <p>
                    &Egrave; gi&agrave; presente un amministratore e l'operazione di setup del sito &egrave; stata effettuata con successo.
                </p>
                <? } else {
                if(empty($_POST['pwd1'])) { ?>
                <h2>Creazione Amministratore</h2>
                <form action="setup.php" method="post" onsubmit="return validate()">
                    <p>
                        Inserire due volte la password di amministratore. Con questa sarà possibile in seguito creare, modificare e cancellare il torneo.
                    </p>
                    <table>
                        <tr>
                            <td><label for="username">Username:</label></td>
                            <td><input type="text" class="text_big" id="username" name="username"/></td>
                        </tr>
                        <tr>
                            <td><label for="pwd1">Password:</label></td>
                            <td><input type="password" class="text_big" id="pwd1" name="pwd1"/></td>
                        </tr>
                        <tr>
                            <td><label for="pwd2">Ripeti password:</label></td>
                            <td><input type="password" class="text_big" id="pwd2" name="pwd2"/></td>
                        </tr>
                        <tr><td><br/></td></tr>
                        <tr>
                            <td colspan="2"><h4>Parametri DataBase</h4></td>
                        </tr>
                        <tr>
                            <td><label for="dbhost">Host:</label></td>
                            <td><input type="text" class="text_big" id="dbhost" name="dbhost" value="localhost"/></td>
                        </tr>
                        <tr>
                            <td><label for="dbuser">User:</label></td>
                            <td><input type="text" class="text_big" id="dbuser" name="dbuser"/></td>
                        </tr>
                        <tr>
                            <td><label for="dbpwd1">Password:</label></td>
                            <td><input type="password" class="text_big" id="dbpwd1" name="dbpwd1"/></td>
                        </tr>
                        <tr>
                            <td><label for="dbpwd2">Ripeti password:</label></td>
                            <td><input type="password" class="text_big" id="dbpwd2" name="dbpwd2"/></td>
                        </tr>
                        <tr>
                            <td><label for="dbname">Database:</label></td>
                            <td><input type="text" class="text_big" id="dbname" name="dbname"/></td>
                        </tr>
                        <tr>
                            <td colspan="2"><input class="mybutton" type="submit" id="create_amm" name="create_amm" value="Registra"/></td>
                        </tr>
                    </table>
                </form>
                <? } else {

                    $username = $_POST['username'];
                    $host = $_POST['dbhost'];
                    $user = $_POST['dbuser'];
                    $pass = md5($_POST['pwd1']);
                    $db = $_POST['dbname'];
                    $sql_file = "fantaDB.sql";
                    
                    mysql_query("INSERT INTO utenti(username,pwd,tipo) VALUES('$username','$pass','ADMIN')", $mysql);
                    $_SESSION['tipo'] = 'ADMIN';
                    $_SESSION['username'] = $username;

                    close_db();
                    ?>
                    <h2>Operazione riuscita</h2>
                    <p>
                        Account amministratore creato con successo!
                    </p>
               <? }
            } ?>
            <br/><br/>
        </div>
	</div>
</div>
<div id="footer">
	<p>Copyright (c) 2009 <a href="http://www.andreamartelli.it">Andrea Martelli</a>. All rights reserved.</p>
</div>
</body>
</html>