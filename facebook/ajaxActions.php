<?php

include_once dirname(dirname(__FILE__)) . "/engine/engine.php";
//include_once( "../engine/engine.php" );

$action = $_REQUEST['action'];

if($action) {

    call_user_func($action);

}

function post() {

    global $mysql;
    
    $text = $_POST['message'];
    $safe_text = mysql_real_escape_string($text);

    //array associativo squadre-(presidenti,fb_uid) x link vari
    $result = mysql_query("SELECT s.nome,u.username,u.fb_uid FROM squadre as s join utenti as u on s.presidente = u.username", $mysql);
    while($row = mysql_fetch_assoc($result)) {
        $users[$row['username']] = array('squadra' => $row['nome'], 'fb_uid' => $row['fb_uid']);
    }

    $id = stream_post_put($safe_text);

    if($id && $id > 0) {

        $item = array('id' => $id, 'user' => $_SESSION['username'],
                      'timestamp' => date('Y-m-d H:i:s', time()), 'content' => $text);
        $post = array('item' => $item);

        $html = format_post($post, $users);

        echo $html;

        // Notifica via Facebook
        notifica_post_facebook();
        
    } else {
        
        echo "ERRORE";
    }
    
}

function comment() {

    global $mysql;
    
    $text = $_REQUEST['message'];
    $safe_text = mysql_real_escape_string($text);
    $ref = $_REQUEST['reference'];

    //array associativo squadre-(presidenti,fb_uid) x link vari
    $result = mysql_query("SELECT s.nome,u.username,u.fb_uid FROM squadre as s join utenti as u on s.presidente = u.username", $mysql);
    while($row = mysql_fetch_assoc($result)) {
        $users[$row['username']] = array('squadra' => $row['nome'], 'fb_uid' => $row['fb_uid']);
    }

    // Inserimento del post nel Database
    $id = stream_comment_put($safe_text, $ref);
    if($id && $id > 0) {

        $comment = array('id' => $id, 'user' => $_SESSION['username'],
                         'timestamp' => date('Y-m-d H:i:s', time()), 'content' => $text);
        $html = format_comment($comment, $users, true);

        echo $html;
        
    } else {

        echo 'ERRORE';
    }
}

function delete() {

    global $mysql;

    $id = $_REQUEST['id'];

    $result = mysql_query("DELETE FROM stream WHERE id=$id OR reference=$id", $mysql);

    if($result) {
        echo "OK";
    } else {
        echo "ERRORE";
    }
}

function get_older_post() {

    $start = $_REQUEST['start'];

    $wall = get_stream_content($start);

    if($wall) {

        $html = format_stream_content($wall);

        echo $html;

    } else {

        echo "ERRORE";
    }
}

function update_stream() {

    $datetime = $_REQUEST['timestamp'];

    $wall = get_stream_content(0, 0, $datetime);

    if($wall) {

        $news = format_new_stream_content($wall);

        echo json_encode($news);

    } else
        echo '';
    
}

?>