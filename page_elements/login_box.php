<?

session_start();

?>

<div class="box" id="loginbox">
    <h3>Login</h3>
    <?php

    if(!isloggedin()) { ?>
    <!-- Is not logged in -->
    <form id="login" method="post" action="<?= $baseUrl ?>engine/login.php">
        <input type="hidden" name="referrer" value="<?echo $_SERVER['PHP_SELF']?>" id="referrer"/>
        <input type="hidden" name="logintype" value="normal" id="logintype"/>
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
    ?>

    <a href="<?= $baseUrl ?>engine/login.php?action=logout">Logout</a>

    <? } ?>
</div>
<br/>