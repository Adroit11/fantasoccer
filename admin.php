<?php
session_start();

@include("engine/engine.php");

admin_gatekeeper();

if(!empty($_POST['confirm']) && $_POST['confirm']==true) {

    // reset del campionato
    delete_championship();
    
}
// Creazione NUOVO TORNEO
if(!empty($_POST['action']) && $_POST['action']=="create") {
    
    $n_giornate = $_POST['giornate'];
    $torneo = $_POST['torneo'];
    $team = $_POST['team'];

    start_transaction();
    
    // Creazione 'giornate'
    create_sportdays($n_giornate, $torneo);

    // Creazione 'squadre'
    create_teams($team);

    // Creazione 'calendario'
    create_calendar($team, $n_giornate, $torneo);

    // Creazione 'giocatori' - Download ed estrazione
    download_players_list();

    commit();
    close_db();

    // Redirect a home page
    header("location: index.php");
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
    </head>
    <body>
        <script type="text/javascript">
            function isNumeric(input) {
                return (input - 0) == input && input.length > 0;
            }

            function confirm_delete() {
                return confirm("Confermi la cancellazione dell'intero torneo?");
            }

            function validate_num_teams() {
                var n = document.getElementById("num").value;
                if(n!=null && n>0) {
                    var table = document.getElementById("form_table");
                    var row = document.createElement("TR");
                    var td = document.createElement("TD");
                    var h4 = document.createElement("h4");
                    h4.appendChild(document.createTextNode("Squadre partecipanti"));
                    td.appendChild(h4);
                    row.appendChild(td);
                    table.appendChild(row);
                    for(var i=1; i<=n; i++) {
                        var row = document.createElement("TR");
                        var td1 = document.createElement("TD");
                        var label = document.createElement("label");
                        label.setAttribute("for", "team"+i);
                        var labeltext = document.createTextNode("Squadra "+i);
                        label.appendChild(labeltext);
                        td1.appendChild(label);
                        var td2 = document.createElement("TD");
                        var textbox = document.createElement("input");
                        textbox.setAttribute("type", "text");
                        textbox.setAttribute("class", "text_big");
                        textbox.setAttribute("id", "team[]");
                        textbox.setAttribute("name", "team[]");
                        td2.appendChild(textbox);
                        row.appendChild(td1);
                        row.appendChild(td2);
                        if(i==n) {
                            var td3 = document.createElement("TD");
                            var next = document.createElement("A");
                            next.setAttribute("href", "javascript:validate_team_names()");
                            next.appendChild(document.createTextNode("Prosegui"));
                            td3.appendChild(next);
                            row.appendChild(td3);
                        }
                        table.appendChild(row);
                    }
                }
            }

            function validate_team_names() {
                var n = document.getElementById("num").value;
                var names_ok = true;
                var names = document.getElementsByName("team[]");
                for(var k=0; k<names.length && names_ok; k++) {
                    if(names[k].value==null || names[k].value=="")
                        names_ok=false;
                }
                if(names_ok) {
                    var table = document.getElementById("form_table");
                    var row = document.createElement("TR");
                    var td = document.createElement("TD");
                    var h4 = document.createElement("h4");
                    h4.appendChild(document.createTextNode("Numero giornate"));
                    td.appendChild(h4);
                    row.appendChild(td);
                    table.appendChild(row);
                    var row2 = document.createElement("TR");
                    var td1 = document.createElement("TD");
                    var td2 = document.createElement("TD");
                    var lab = document.createElement("Label");
                    lab.setAttribute("for", "giornate");
                    lab.appendChild(document.createTextNode("Seleziona le giornate"));
                    td1.appendChild(lab);
                    var ngiorn = document.createElement("input");
                    ngiorn.setAttribute("type", "text");
                    ngiorn.setAttribute("class", "text_big");
                    ngiorn.setAttribute("id", "giornate");
                    ngiorn.setAttribute("name", "giornate");
                    td2.appendChild(ngiorn);
                    row2.appendChild(td1);
                    row2.appendChild(td2);
                    var td3 = document.createElement("TD");
                    var next = document.createElement("A");
                    next.setAttribute("href", "javascript:validate_matches()");
                    next.appendChild(document.createTextNode("Prosegui"));
                    td3.appendChild(next);
                    row2.appendChild(td3);
                    table.appendChild(row2);
                } else {
                    alert("Inserisci i nomi di tutte le squadre");
                }
            }

            function validate_matches() {
                var numsq = document.getElementById("num").value;
                var n = document.getElementById("giornate").value;
                if(isNumeric(n)) {
                    var ngironi = parseInt(n/(numsq-1));
                    var gtorn = n - ngironi*(numsq-1);
                    var table = document.getElementById("form_table");
                    var row1 = document.createElement("TR");
                    var td = document.createElement("TD");
                    var h4 = document.createElement("h4");
                    td.setAttribute("colspan", "2")
                    h4.appendChild(document.createTextNode("Giornate aggiuntive : Mini-Torneo"));
                    td.appendChild(h4);
                    row1.appendChild(td);
                    table.appendChild(row1);
                    var row2 = document.createElement("TR");
                    var td2 = document.createElement("TD");
                    td2.setAttribute("colspan", "3");
                    row2.appendChild(td2);
                    td2.appendChild(document.createTextNode("Gironi: "+ngironi+", Giornate di mini-torneo: "+gtorn));
                    table.appendChild(row2);
                    if(gtorn>0) {
                        var row3 = document.createElement("TR");
                        var td3 = document.createElement("TD");
                        td3.setAttribute("colspan", "3");
                        row3.appendChild(td3);
                        td3.appendChild(document.createTextNode("Scelta delle giornate:"));
                        table.appendChild(row3);
                    }
                    for(var i=1; i<=gtorn; i++) {
                        var row = document.createElement("TR");
                        var td1 = document.createElement("TD");
                        var td2 = document.createElement("TD");
                        td1.appendChild(document.createTextNode("Giornata "+i));
                        var select = document.createElement("select");
                        select.setAttribute("size", "1");
                        select.setAttribute("cols", gtorn);
                        select.setAttribute("name", "torneo[]");
                        select.setAttribute("id", "torneo[]");
                        for(var j=1; j<=n; j++) {
                            var opt = document.createElement("option");
                            opt.setAttribute("value", j);
                            opt.appendChild(document.createTextNode(j));
                            select.appendChild(opt);
                        }
                        td2.appendChild(select);
                        row.appendChild(td1);
                        row.appendChild(td2);
                        table.appendChild(row);
                    }
                    var row4 = document.createElement("TR");
                    var td4 = document.createElement("TD");
                    var next = document.createElement("INPUT");
                    next.setAttribute("type", "submit");
                    next.setAttribute("class", "mybutton");
                    next.setAttribute("method", "post");
                    next.setAttribute("value", "COMPLETA!");
                    td4.appendChild(next);
                    row4.appendChild(td4);
                    table.appendChild(row4);
                } else {
                    alert("Devi inserire un numero!");
                }
            }
        </script>
        <? @include("page_elements/header.php"); ?>
        <div id="content">
            <div id="colOne">
                <div id="logo"></div>
                <? @include("page_elements/login_box.php"); ?>
                <? @include("page_elements/menu_lat.php"); ?>
            </div>
            <div id="colTwo">
                <div class="box">
                    <!-- Verifica esistenza torneo! -->
                    <?php
                    if(!$mysql) {
                        system_error("Impossibile collegarsi al database");
                    } else {
                        $teams = mysql_query("SELECT * FROM squadre", $mysql);
                        $days = mysql_query("SELECT * FROM giornate", $mysql);
                        $matches = mysql_query("SELECT * FROM calendario", $mysql);
                        $players = mysql_query("SELECT * FROM giocatori", $mysql);
                        if(mysql_num_rows($teams)>0 && mysql_num_rows($days)>0 && mysql_num_rows($matches)>0) {
                            $exists = true;
                        }
                        else {
                            $exists = false;
                        }
                        close_db();
                    }
                    if($exists) { ?>
                    <h2>Cancellazione torneo</h2>
                    <p style="color: red;">Il DataBase contiene un torneo precedentemente creato. Vuoi eliminarlo?</p>
                    <form action="admin.php" method="post" onsubmit="return confirm_delete()">
                        <input type="checkbox" id="confirm" name="confirm" value="Confermi la cancellazione?" />
                        <input type="submit" class="mybutton" value="Cancella">
                    </form>
                    <? } else { ?>
                    <h2>Creazione nuovo torneo</h2>
                    <p class="box">
                        <form action="admin.php" method="post" name="creation">
                            <input type="hidden" name="action" value="create"/>
                            <table align="center" id="form_table">
                                <tr>
                                    <td colspan="3"><h4>Numero squadre</h4></td>
                                </tr>
                                <tr>
                                    <td><label for="num">Seleziona:</label></td>
                                    <td>
                                        <select size="1" cols="4" name="num" id="num">
                                            <option selected value="0">
                                            <option value="4">4
                                            <option value="6">6
                                            <option value="8">8
                                            <option value="10">10
                                            <option value="12">12
                                            <option value="14">14
                                            <option value="16">16
                                            <option value="18">18
                                            <option value="20">20
                                        </select>
                                    </td>
                                    <td><a href="javascript:validate_num_teams()">Prosegui</a></td>
                                </tr>
                            </table>
                        </form>
                    </p>
                    <? } ?>
                </div>
            </div>
        </div>
        <div id="footer">
            <p>Copyright (c) 2009 <a href="http://www.andreamartelli.it">Andrea Martelli</a>. All rights reserved.</p>
        </div>
    </body>
</html>