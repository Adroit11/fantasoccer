<?php

include_once dirname(__FILE__) . "/engine.php";
//include_once( "engine.php" );

$squadre = mysql_query("SELECT nome FROM squadre WHERE presidente IS NULL", $mysql);
$nomi = mysql_query("SELECT presidente FROM squadre WHERE presidente IS NOT NULL", $mysql);
$managers = mysql_query("SELECT count(*) FROM utenti WHERE tipo='MANAGER'", $mysql);
$n_man = mysql_fetch_row($managers);
$n_man = $n_man[0];
$n_sq = mysql_num_rows($squadre);

if(!empty($_SESSION['tipo']) || !empty($_SESSION['username']) || $n_sq==0) {
    
    system_error("ATTENZIONE! Sei già loggato o non ci sono squadre libere.");
    
} else {
    
    $user = $_POST['user'];
    $pwd = $_POST['pwd1'];
    $uid = $_POST['fb_uid'];
    $type = $_POST['type'];
    $team = $_POST['squadra'];

    // Prova a ottenere la sessione di collegamento a Facebook (null se l'utente non è loggato)
    $fb_session = get_fb_session();

    if(uid_used($uid)) {
        system_error("ATTENZIONE: l'account Facebook attualmente attivo è già associato ad un altro utente!
            <br/>Effettare il logout dell'account Facebook attualmente in uso!");
    }

    if(!empty($user) && !empty($pwd)) {
        $pwd = md5($pwd);
        mysql_query("BEGIN", $mysql);

        $uid = ($uid != null && $uid != "") ? "'" + $uid + "'" : $uid = "null";
        $ok1 = mysql_query("INSERT INTO utenti VALUES('". $user ."','$pwd','$type',$uid)", $mysql);
        $ok2 = mysql_query("UPDATE squadre SET presidente = '$user' WHERE nome='$team'", $mysql);
        if(!$ok1 || !$ok2) {
            mysql_query("ROLLBACK", $mysql);
            header("location: {$baseUrl}engine/register.php?error=true");
        } else {
            mysql_query("COMMIT", $mysql);
            $_SESSION['tipo'] = $type;
            $_SESSION['username'] = $user;
            header("location: {$baseUrl}index.php");
        }
    }
}
close_db();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<!--
Design by Free CSS Templates
http://www.freecsstemplates.org
Released for free under a Creative Commons Attribution 2.5 License

Site Engineering by Andrewww
-->

<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>FantaTorneo *New Generation*</title>
        <link href="<? echo $baseUrl ?>style.css" rel="stylesheet" type="text/css" />
        <link rel="shortcut icon" href="../pics/favicon.ico"/>
        <script type="text/javascript" src="javascript/jquery-1.3.2.min.js"></script>
        <script type="text/javascript" src="javascript/effects.core.js"></script>
        <script type="text/javascript" src="javascript/jquery.qtip-1.0.0-rc3.min.js"></script>
        <script type="text/javascript">
            function validate() {
                var nomi = new Array();
                <?php
                while($row = mysql_fetch_row($nomi)) {
                    print("nomi.push(\"".$row[0]."\");\n");
                }?>
                var n = document.getElementById("user").value;
                var p1 = document.getElementById("pwd1").value;
                var p2 = document.getElementById("pwd2").value;
                var exists = false;
                // Facebook authentication check
                var uid_fb = document.getElementById("fb_uid").value;
                if(uid_fb == "") {
                    alert("Effettua la procedura di autenticazione a Facebook per godere di tutte le funzioni del sito");
                    return false;
                }

                for(var i=0; i<nomi.length && !exists; i++) {
                    if(n == nomi[i]) {
                        exists = true;
                    }
                }
                if(n==null || n=="") {
                    alert("Inserisci lo username");
                    return false;
                } else {
                    if(p1!=p2 || p1=="") {
                        alert("Inserisci due volte la stessa password");
                        return false;
                    } else {
                        if(exists) {
                            alert("Il nome specificato è già associato ad un'altra squadra");
                            return false;
                        } else {
                            return true;
                        }
                    }
                }
            }
        </script>
    </head>
    <body>
        <? @include("../page_elements/header.php"); ?>
        <div id="content">
            <div id="colOne">
                <div id="logo"></div>
                <? @include("../page_elements/login_box.php"); ?>
                <? @include("../page_elements/menu_lat.php"); ?>
            </div>
            <div id="colTwo">
                <div>
                    <h2>Registrazione nuovo utente</h2>
                    <h3>Inserisci i dati nel form per iscriverti al torneo!</h3><br/>
                    <form action="register.php" method="post" onsubmit="return validate()">
                        <table>
                            <tr>
                                <td colspan="2"><h4>Informazioni personali</h4></td>
                            </tr>
                            <tr>
                                <td>
                                    <label for="user">Username: </label>
                                </td>
                                <td>
                                    <input type="text" class="text_big" id="user" name="user"/>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label for="pwd1">Password: </label>
                                </td>
                                <td>
                                    <input type="password" class="text_big" id="pwd1" name="pwd1"/>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label for="pwd2">Conferma password: </label>
                                </td>
                                <td>
                                    <input type="password" class="text_big" id="pwd2" name="pwd2"/>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label for="facebook">Identificazione Facebook: </label>
                                </td>
                                <td>
                                    <?
                                    if($fb_session) {
                                        $visib_check = "display: inline;";
                                        $visib_butt = "display: none;";
                                    } else {
                                        $visib_check = "display: none;";
                                        $visib_butt = "display: inline;";
                                    } ?>
                                    <img src="../images/check.png" alt="Ok" id="fb_check" height="20px" width="20px" style="padding-left: 12px; <?= $visib_check ?>"/>
                                    <span id="fb_button" style="<?= $visib_butt ?>"><fb:login-button perms="publish_stream,user_online_presence,email">Collega a Facebook</fb:login-button></span>
                                    <input type="hidden" name="fb_uid" id="fb_uid" value=""/>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <br/>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2"><h4>Seleziona la squadra che vuoi dirigere</h4></td>
                            </tr>
                            <tr>
                                <td>
                                    <label for="squadra">Squadra: </label>
                                </td>
                                <td>
                                    <select size="1" cols="10" name="squadra" id="squadra">
                                        <?php
                                        while($row = mysql_fetch_row($squadre)) {
                                            echo '<option value="'.$row[0].'">'.$row[0];
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr><td><br/></td></tr>
                            <tr>
                                <td colspan="2"><h4>Tipo di utente</h4></td>
                            </tr>
                            <tr>
                                <td>
                                    <label for="type">Tipo: </label>
                                </td>
                                <td>
                                    <select size="1" cols="10" name="type" id="type">
                                        <option value="USER">Utente semplice</option>
                                        <?php
                                        if($n_man==0) {
                                            echo '<option value="MANAGER">Manager';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="submit" class="mybutton" value="Conferma" name="Conferma" title="Conferma"/>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
            </div>
        </div>
        <div id="footer">
            <p>Copyright (c) 2009 <a href="http://www.andreamartelli.it">Andrea Martelli</a>. All rights reserved.</p>
        </div>

        <!-- Facebook connection handling -->
        <div id="fb-root"></div>
        <script type="text/javascript" src="http://connect.facebook.net/it_IT/all.js"></script>
        <script type="text/javascript">
            FB.init({appId: '<?= $fb_appid ?>',
                status: true,
                cookie: true,
                xfbml: true});

            FB.Event.subscribe('auth.login', function(response) {
                if(response.session) {
                    document.getElementById('fb_uid').value = response.session.uid;
                    document.getElementById('fb_button').setAttribute("style", "display: none;");
                    document.getElementById('fb_check').setAttribute("style", "display: inline;");
                } else {
                    // user not logged in
                }
            });
        </script>
    </body>
</html>