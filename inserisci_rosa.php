<?php

include_once dirname(__FILE__) . "/engine/engine.php";
//include_once( "engine/engine.php" );


if( !isloggedin() ) {

    access_denied();
    
} else {

    $team = $_POST['team'];
    $result = mysql_query("SELECT presidente FROM squadre WHERE nome='$team'", $mysql);
    $president = mysql_fetch_row($result);
    $president = $president[0];
        
    $squadre= mysql_query("SELECT nome FROM squadre", $mysql);

    if(!empty($team)) {
        
        if($president != $_SESSION['username'] && $_SESSION['tipo']!="MANAGER") {
            access_denied();
        }

        $all = mysql_query("SELECT nome,ruolo FROM giocatori ORDER BY nome", $mysql);
        $allmy = mysql_query("SELECT nome, valore, ruolo FROM giocatori WHERE squadra='$team' ORDER BY nome", $mysql);
        if(!$all || !$allmy) system_error("Errore: impossibile ottenere la rosa");

        if(mysql_num_rows($allmy) > 0) {
            $presente = true;
        }

        while($r = mysql_fetch_row($all)) {
            if($r[1]=='P') $allP[] = $r[0];
            else if($r[1]=='D') $allD[] = $r[0];
            else if($r[1]=='C') $allC[] = $r[0];
            else if($r[1]=='A') $allA[] = $r[0];
        }

        while($r = mysql_fetch_row($allmy)) {
            if($r[2]=='P') $P[] = array($r[0],$r[1]);
            else if($r[2]=='D') $D[] = array($r[0],$r[1]);
            else if($r[2]=='C') $C[] = array($r[0],$r[1]);
            else if($r[2]=='A') $A[] = array($r[0],$r[1]);
        }
    }
    if(ismanagerloggedin() && $_POST['action']=="refresh") {
        // Aggiornamento rosa
        //$team = $_POST['team'];
        $gioc = $_POST['giocatori'];
        $values = $_POST['valore'];
        mysql_query("BEGIN", $mysql);
        $ok1 = mysql_query("UPDATE giocatori SET squadra=NULL, valore=NULL WHERE squadra='$team'", $mysql);
        for($i=0; $i<count($gioc); $i++) {
            $ok2 = mysql_query("UPDATE giocatori SET squadra='$team', valore=$values[$i] WHERE nome='$gioc[$i]'", $mysql);
        }

        // Inserimento evento nello stream
        if($_POST['presente'] == true) {
            stream_event_put('TEAM_MOD', null, $president);
        } else {
            stream_event_put('TEAM_CREATE', null, $president);
        }

        if(!$ok1 || !$ok2) {
            rollback();
            system_error("Errore: impossibile aggiornare la rosa");
        }

        commit();

        header("location: index.php");
    }
    close_db();
}
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
        <title>FantaTorneo *New Generation*</title>
        <link rel="shortcut icon" href="pics/favicon.ico"/>
        <? @include("page_elements/scripts.php"); ?>
        <script type="text/javascript">
            function validate() {
                var numfs = document.getElementsByName("valore[]");
                var numok = true;
                for(var i=0; i<numfs.length && numok; i++) {
                    if(numfs[i].value==null || numfs[i].value=="" || !isNumeric(numfs[i].value)) {
                        alert('I valori dei giocatori devono essere tutti numerici');
                        numok = false;
                    }
                }
                return numok;
            }
        </script>
    </head>
    <body>
        <? @include("page_elements/header.php"); ?>
        <div id="content">
            <div id="colOne">
                <div id="logo"></div>
                <? @include("page_elements/login_box.php"); ?>
                <? @include("page_elements/menu_lat.php"); ?>
            </div>
            <div id="colTwo">
                <?php
                if($_SESSION['tipo']=="MANAGER") {
                    if(empty($team)) { ?>
                        <div class="box">
                            <h2>Seleziona squadra</h2>
                            <form action="inserisci_rosa.php" method="post">
                                <table align="center">
                                    <tr>
                                        <td>
                                            <label for="team">Seleziona squadra: </label>
                                        </td>
                                        <td>
                                            <select size="1" cols="10" name="team" id="team">
                                                <?php
                                                while($row = mysql_fetch_row($squadre)) {
                                                    echo '<option value="'.$row[0].'">'.$row[0];
                                                }
                                                ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr><td height="15"></td></tr>
                                    <tr>
                                        <td align="center" colspan="2">
                                            <input type="submit" class="mybutton" name="OK" value="OK" title="OK"/>
                                        </td>
                                    </tr>
                                </table>
                            </form>
                        </div>
                <? } else { ?>
                <!-- Mostra rosa della squadra EDITABILE -->
                <div class="box">
                <form action="inserisci_rosa.php" method="post" onsubmit="return validate()">
                    <input type="hidden" name="action" value="refresh" id="action"/>
                    <input type="hidden" name="team" value="<?echo $team;?>" id="team"/>
                    <table align="center">
                        <tr>
                            <td colspan="2" align="center"><h2>Rosa di <? echo $team; ?></h2></td>
                        </tr>
                        <tr>
                            <td colspan="2" align="center"><h4>Portieri</h4></td>
                        </tr>
                        <? for($i=0; $i<3; $i++) { ?>
                        <tr>
                            <td width="100">
                                <label for="<?echo "portiere".($i+1);?>"><?echo "Portiere ".($i+1);?></label>
                            </td>
                            <td>
                                <select size="1" cols="10" name="giocatori[]" id="giocatori[]">
                                    <?php
                                    for($j=0; $j<count($allP); $j++) {
                                        $select = "";
                                        if(!empty($P[$i][0]) && $P[$i][0]==$allP[$j]) {
                                            $select="selected=\"selected\"";
                                        }
                                        echo '<option value="'.$allP[$j].'" '.$select.'>'.$allP[$j];
                                    }
                                    ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="text" size="1" name="valore[]" id="valore[]" value="<? echo $P[$i][1] ?>"/>
                            </td>
                        </tr>
                        <? } ?>
                        <tr><td><br/></td></tr>
                        <tr>
                            <td colspan="2" align="center"><h4>Difensori</h4></td>
                        </tr>
                        <? for($i=0; $i<8; $i++) { ?>
                        <tr>
                            <td>
                                <label for="<?echo "difensore".($i+1);?>"><?echo "Difensore ".($i+1);?></label>
                            </td>
                            <td>
                                <select size="1" cols="10" name="giocatori[]" id="giocatori[]">
                                    <?php
                                    for($j=0; $j<count($allD); $j++) {
                                        $select = "";
                                        if(!empty($D[$i][0]) && $D[$i][0]==$allD[$j]) {
                                            $select="selected=\"selected\"";
                                        }
                                        echo '<option value="'.$allD[$j].'" '.$select.'>'.$allD[$j];
                                    }
                                    ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="text" size="1" name="valore[]" id="valore[]" value="<? echo $D[$i][1] ?>"/>
                            </td>
                        </tr>
                        <? } ?>
                        <tr><td><br/></td></tr>
                        <tr>
                            <td colspan="2" align="center"><h4>Centrocampisti</h4></td>
                        </tr>
                        <? for($i=0; $i<8; $i++) { ?>
                        <tr>
                            <td>
                                <label for="<?echo "centrocampista".($i+1);?>"><?echo "Centrocampista ".($i+1);?></label>
                            </td>
                            <td>
                                <select size="1" cols="10" name="giocatori[]" id="giocatori[]">
                                    <?php
                                    for($j=0; $j<count($allC); $j++) {
                                        $select = "";
                                        if(!empty($C[$i][0]) && $C[$i][0]==$allC[$j]) {
                                            $select="selected=\"selected\"";
                                        }
                                        echo '<option value="'.$allC[$j].'" '.$select.'>'.$allC[$j];
                                    }
                                    ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="text" size="1" name="valore[]" id="valore[]" value="<? echo $C[$i][1] ?>"/>
                            </td>
                        </tr>
                        <? } ?>
                        <tr><td><br/></td></tr>
                        <tr>
                            <td colspan="2" align="center"><h4>Attaccanti</h4></td>
                        </tr>
                        <? for($i=0; $i<6; $i++) { ?>
                        <tr>
                            <td>
                                <label for="<?echo "attaccante".($i+1);?>"><?echo "Attaccante ".($i+1);?></label>
                            </td>
                            <td>
                                <select size="1" cols="10" name="giocatori[]" id="giocatori[]">
                                    <?php
                                    for($j=0; $j<count($allA); $j++) {
                                        $select = "";
                                        if(!empty($A[$i][0]) && $A[$i][0]==$allA[$j]) {
                                            $select="selected=\"selected\"";
                                        }
                                        echo '<option value="'.$allA[$j].'" '.$select.'>'.$allA[$j];
                                    }
                                    ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="text" size="1" name="valore[]" id="valore[]" value="<? echo $A[$i][1] ?>"/>
                            </td>
                        </tr>
                        <? } ?>
                        <tr><td><br/></td></tr>
                        <tr>
                            <td align="center" colspan="3">
                                <input type="submit" class="mybutton" name="Conferma" title="Conferma" value="Conferma"/>
                            </td>
                        </tr>
                        <input type="hidden" name="presente" value="<?= $presente ?>"/>
                    </table>
                </form>
                </div>
                <? } ?>
            <? } else { ?>
                <!-- Mostra rosa della squadra dell'utente NON EDITABILE -->
                <div class="box">
                    <table align="center">
                        <tr>
                            <td colspan="2" align="center"><h2>Rosa di <? echo $team; ?></h2></td>
                        </tr>
                        <tr>
                            <td colspan="2" align="center"><h4>Portieri</h4></td>
                        </tr>
                        <? for($i=0; $i<3; $i++) { ?>
                        <tr>
                            <td>
                                <? echo $P[$i][0]; ?>
                            </td>
                            <td>
                                <? echo $P[$i][1]; ?>
                            </td>
                        </tr>
                        <? } ?>
                        <tr><td><br/></td></tr>
                        <tr>
                            <td colspan="2" align="center"><h4>Difensori</h4></td>
                        </tr>
                        <? for($i=0; $i<8; $i++) { ?>
                        <tr>
                            <td>
                                <? echo $D[$i][0]; ?>
                            </td>
                            <td>
                                <? echo $D[$i][1]; ?>
                            </td>
                        </tr>
                        <? } ?>
                        <tr><td><br/></td></tr>
                        <tr>
                            <td colspan="2" align="center"><h4>Centrocampisti</h4></td>
                        </tr>
                        <? for($i=0; $i<8; $i++) { ?>
                        <tr>
                            <td>
                                <? echo $C[$i][0]; ?>
                            </td>
                            <td>
                                <? echo $C[$i][1]; ?>
                            </td>
                        </tr>
                        <? } ?>
                        <tr><td><br/></td></tr>
                        <tr>
                            <td colspan="2" align="center"><h4>Attaccanti</h4></td>
                        </tr>
                        <? for($i=0; $i<6; $i++) { ?>
                        <tr>
                            <td>
                                <? echo $A[$i][0]; ?>
                            </td>
                            <td>
                                <? echo $A[$i][1]; ?>
                            </td>
                        </tr>
                        <? } ?>
                        <tr><td><br/></td></tr>
                    </table>
                </div>
            <? } ?>
            </div>
        </div>
        <div id="footer">
            <p>Copyright (c) 2009 <a href="http://www.andreamartelli.it">Andrea Martelli</a>. All rights reserved.</p>
        </div>
    </body>
</html>