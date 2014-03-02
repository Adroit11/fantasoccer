<?

session_start();

// Indica che l'utente è connesso a Facebook con un generico account
$fbme = null;
// Indica che l'utente è online nel sito e su Facebook con l'account corrispondente
$fbconnected = false;

$facebook = get_fb_instance();
try {
    $session = get_fb_session();

    if ($session) {
        $uid = $facebook->getUser();
        $fbme = $facebook->api('/me');
    }

    $fbconnected = ($fbme && $_SESSION['fb_uid'] == $uid);

    if(isloggedin() && $fbconnected) {
        $data = $facebook->api(array('method' => 'fql.query',
                                     'query' => "SELECT publish_stream FROM permissions WHERE uid='" . $_SESSION['fb_uid'] . "'"));
        // Indica che l'utente non ha i permessi specificati e occorre richiederglieli
        $askpermissions = ($data[0]['publish_stream'] == 0);
    }

} catch (FacebookApiException $e) {
    //echo "Eccezione";
}
        
if ($askpermissions) { ?>

    <script type="text/javascript">
        $(document).ready(function() {
            FB.login(function(response) {
                if (response.session) {
                    if (!response.perms) {
                        alert('ATTENZIONE!! Per godere di tutte le nuove funzionalità del sito, devi concedere i permessi a Facebook');
                    }
                } else {
                    alert('ERRORE: non sei collegato a Facebook!');
                }
            }, {perms:'publish_stream'});
        });
    </script>

<? } ?>

<div class="box" id="loginbox">
    <h3>Login</h3>
    <?php

    if(!isloggedin()) { ?>
    <!-- Is not logged in -->
    <form id="login" method="post" action="<?= $baseUrl ?>engine/login.php">
        <input type="hidden" name="referrer" value="<?echo $_SERVER['PHP_SELF']?>" id="referrer"/>
        <input type="hidden" name="logintype" value="normal" id="logintype"/>
        <input type="hidden" name="facebook_uid" value="0" id="facebook_uid"/>
        <table>
            <tr>
                <td><label for="username">Username</label></td>
                <td><input class="textbox" type="text" name="username" id="username"/></td>
            </tr>
            <tr>
                <td><label for="pwd">Password</label></td>
                <td><input class="textbox" type="password" name="pwd" id="pwd"/></td>
                <td><input class="mybutton" type="submit" id="submit" name="submit" title="Ok" value="Ok"> </td>
            </tr>
            <tr>
                <td colspan="2" >
                    <div id="fb_button">
                        <? if(!$fbme) { ?>
                            <fb:login-button perms="publish_stream,user_online_presence,email">Entra con Facebook</fb:login-button>
                        <? } else { ?>
                            <a id="facebook_button" class="fb_button fb_button_medium"><span class="fb_button_text">Entra con Facebook</span></a>
                        <? } ?>
                        <img src="<?= $baseUrl ?>images/check.png" height="20px" width="20px" alt="Loggato in Facebook" id="facebook_logged" style="display: none; margin-right: 10px; float: right;"/>
                        <input type="hidden" id="clickedlogin" name="clickedlogin" value="false"/>
                    </div>
                </td>
            </tr>
            <? if(!empty($_GET[login]) && $_GET[login]=='failed') { ?>
            <tr>
                <td colspan="2" style="color: red">Login fallito!</td>
            </tr>
            <? } ?>
            <tr>
                <td colspan="2"><a href="<?= $baseUrl ?>engine/register.php">Registrati ora!</a></td>
            </tr>
        </table>
    </form>
    <? } else {
    
    // Is logged in

    echo "Ciao " . $_SESSION[username] . "!<br/>";
    echo "Tipo: " . $_SESSION[tipo] . "<br/>";

    if($_SESSION['fb_uid']) {   // Per gli utenti con account Facebook associato

        // Visualizza lo stato online/offline su Facebook?
        $fb_status = ($fbconnected) ? "fb_online" : "fb_offline";

        $status_vis = "display: block;";
        $button_vis = "display: none;";
        
    } else {

        // Visualizza il pulsante per la connessione
        $status_vis = "display: none;";
        $button_vis = "display: block;";
    }
    ?>
    <div id="fb_status" style="<?= $status_vis ?>">
        Status su Facebook
        <img src="<?= $baseUrl ?>images/<?= $fb_status ?>.png" alt="<?= $fb_status ?>" id="status_img" height="20px" width="20px"/>
        <img class="fb_image" src="https://graph.facebook.com/<?= $_SESSION['fb_uid'] ?>/picture"/>
    </div>
    
    <div id="fb_button" style="<?= $button_vis ?>">
        <? if(!$fbme) { ?>
            <fb:login-button perms="publish_stream,user_online_presence,email">Connetti a Facebook</fb:login-button>
        <? } else { ?>
            <a id="facebook_button" class="fb_button fb_button_medium"><span class="fb_button_text">Connetti a Facebook</span></a>
        <? } ?>
        <input type="hidden" id="clickedlogin" name="clickedlogin" value="false"/>
    </div>
    <input type="hidden" name="fb_uid" id="fb_uid" value=""/>
    <br/>

    <a href="<?= $baseUrl ?>engine/login.php?action=logout">Logout</a>

    <? } ?>
</div>

<!-- Facebook connection handling -->
<div id="fb-root"></div>
<!-- Facebook API -->
<script type="text/javascript" src="http://connect.facebook.net/it_IT/all.js"></script>
<script type="text/javascript">    

    var session = null;
    var user_uid = null;

    $(document).ready(function() {

        FB.init({appId: '<?= $fb_appid ?>',
            status: true,
            cookie: true,
            xfbml: true});

        session = FB.getSession();
        if(session != undefined) {
            user_uid = session.uid;
        }

        if(user_uid == null) {
            FB.Event.subscribe('auth.login', function(response) {
                if(response.session) {
                    // Utente loggato in Facebook
                    // Chiamata Ajax per aggiungere UID all'utente nel database
                    manage_fb_connection(response.session.uid);
                } else {
                    // Utente non loggato
                }
            });
        }

        $('#fb_button').click(function() {
            $('#clickedlogin').val("true");
        });

        $('#facebook_button').click(function() {
            $('#clickedlogin').val("true");
            manage_fb_connection(user_uid);
        });
    });

<? if (isloggedin()) { ?>
            function manage_fb_connection(uid) {
                if(uid == null) {
                    temp_session = FB.getSession();
                    uid = temp_session.uid;
                }
                if($('#clickedlogin').val() == 'true') {
                    $.get("<?= $baseUrl ?>facebook/connect_user.php",
                    {uid : uid},
                    function(response) {
                        if(response == 'OK') {
                            document.getElementById('fb_button').setAttribute("style", "display: none;");
                            document.getElementById('status_img').setAttribute("src", "<?= $baseUrl ?>images/fb_online.png");
                            document.getElementById('fb_status').setAttribute("style", "display: block;");
                            $('.fb_image').attr('src', 'https://graph.facebook.com/' + uid + '/picture')
                        } else {
                            alert(response);
                        }
                    });
                }
            }
<? } else { ?>
            function manage_fb_connection(uid) {

                if($('#clickedlogin').val() == 'true') {

                    $.get("<?= $baseUrl ?>facebook/cross_login.php",
                    {uid : uid},
                    function(response) {
                        if(response == 'OK') {
                            // ricarica la pagina
                            window.location.reload();
                        } else {
                            // ! L'account Facebook non è associato a nessuno degli utenti del sito
                            // inserisci campo hidden nel loginbox e segnala la procedura all'utente
                            $('#logintype').val('facebook');
                            $('#facebook_uid').val(uid);
                            $('#facebook_logged').css("display", "inline");

                            $.facebox($('#fb_connect_alert').html());
                        }
                    });
                }
            }
<? } ?>
</script>
    
<div style="display: none;" id="fb_connect_alert">
    <div style="font-size: 14px; width: 500px;">
        <p>
        <h2>Complimenti!</h2>
        Sei loggato a <strong>Facebook</strong>!<br/><br/>

        Il simbolo <img src="<?= $baseUrl ?>images/check.png" alt="ok" height="14px" width="14px"/> indica che il sistema ha temporaneamente
        acquisito il tuo identificativo Facebook.<br/><br/>

        <div style="text-align: center;"><img src="<?= $baseUrl ?>images/facebook_connect.png" alt="Facebook Connect" width="340px"/></div>

        Effettua subito il login a <strong>NewFantaTorneo</strong>, e il tuo profilo Facebook verr&agrave; associato
        permanentemente all'account, permettendoti di accedere a <strong>nuove funzionalità</strong> e di accedere al
        sito con un semplice <strong>click</strong> sul pulsante <strong>"Entra con Facebook"</strong>!
        <br/>
        </p>
    </div>
</div>

<br/>