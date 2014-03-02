<?php

include_once dirname(__FILE__) . "/engine.php";
include_once dirname(dirname(__FILE__)) . "/simpledomparser/simple_html_dom.php";

$THRESHOLD = 6;

$action = $_REQUEST['action'];

if($action)
{
    call_user_func($action);
}

function titolari()
{
    $teamPlayers = json_decode($_REQUEST['teamPlayers']);
    
    $result['gazzetta']         = parse_probform_gazzetta($teamPlayers);
    $result['fantagazzetta']    = parse_probform_fantagazzetta($teamPlayers);
    
    $k_g  = array_keys($result['gazzetta']);
    $k_fg = array_keys($result['fantagazzetta']);
    $playerlist = array_unique(array_merge($k_g, $k_fg));
    
    $summary = get_summary_for_players(array_unique($playerlist));
    
    // Trasforma la struttura dati
    $table = array();
    foreach($playerlist as $p)
    {
        $pdata = array();
        $pdata['nome'] = $p;
        if(($pdata['g'] = array_key_exists($p, $result['gazzetta'])) == true )
        {
            $pdata['squadra'] = $result['gazzetta'][$p][0];
            $pdata['ruolo'] = $result['gazzetta'][$p][1];
        }
        if(($pdata['fg'] = array_key_exists($p, $result['fantagazzetta'])) == true )
        {
            $pdata['squadra'] = $result['fantagazzetta'][$p][0];
            $pdata['ruolo'] = $result['fantagazzetta'][$p][1];
        }
        $table[] = array_merge($pdata, $summary[$p]);
    }
    
    unset($result);
    
    // Sorting
    $role = array();
    $media = array();
    $mediabm = array();
    $nome = array();
    foreach($table as $k => $v)
    {
        $role[$k] = $v['ruolo'];
        $media[$k] = $v['media'];
        $mediabm[$k] = $v['mediabm'];
        $nome[$k] = $v['nome'];
    }
    array_multisort($role, SORT_DESC, $media, SORT_DESC, $mediabm, SORT_DESC, $nome, SORT_ASC, $table);
    
    echo json_encode($table);
}

function parse_probform_gazzetta($team_players)
{
    global $url_probform_gazzetta;
    
    $html = file_get_html($url_probform_gazzetta);
    
    $all_playing = array();
    
    foreach($html->find('span[class="col2"]') as $el)
    {        
        $p_name = $el->innertext;
        //$p_team = clean_team_name($el->parent()->parent()->getAttribute('attr'));
        $p_team_tags = $el->parent()->parent()->find('.title span');
        $p_team = clean_team_name($p_team_tags[0]->innertext);
        $p_tit  = ($el->parent()->first_child()->innertext == '1');
        
        if($p_tit && !empty($p_name))
        {   
            $all_playing[$p_team][] = clean_player_name($p_name, true);
        }
    }
    
    $titolari = filter_only_playing($team_players, $all_playing);
    return $titolari;
}

function parse_probform_fantagazzetta($team_players)
{
    global $url_probform_fantagazzetta;
    
    $html = file_get_html($url_probform_fantagazzetta);
    
    $all_playing = array();
    
    foreach($html->find('.name a') as $el)
    {
        $p_name = $el->innertext;
        $p_link = $el->href;
        $p_link_ss = preg_split('/\//', $p_link);
        $p_team = clean_team_name($p_link_ss[count($p_link_ss) - 2]);
            
        $all_playing[$p_team][] = clean_player_name($p_name);
    }
    
    $titolari = filter_only_playing($team_players, $all_playing);
    return $titolari;
}

/**
 * $team_players is in the form: [['Player', 'Team', 'P'], ['Player', 'Team', 'C'], ... ]
 * $all_playing is in the form: { 'Team1' => ['Player1', 'Player2', ... ],
 *                                'Team2' => ['Player3', 'Player4', ... ] }
 */
function filter_only_playing($team_players, $all_playing)
{
    $titolari = array();
    foreach($team_players as $p)
    {
        // compare only with players from the same team
        $p_name = $p[0];
        //$p_team = clean_team_name($p[1]);
        $p_team = $p[1];
        if(is_playing_fuzzy($p_name, $all_playing[$p_team]))
        {
            $titolari[$p_name] = array($p_team, $p[2]);
        }
    }
    return $titolari;
}

function is_playing_fuzzy($p, $players)
{
    //global $THRESHOLD;
    
    // Try first with the exact match
    if(in_array($p, $players))
    {
        // Found!
        return true;
    }
    else // Then try with word match
    {
        foreach($players as $pla)
        {
            if(preg_match('/\s/',$pla)) // if it has a white space
            {
                $words = preg_split('/ /', $pla); // split in single words
                //$words = split(" ", $pla);  
                if(in_array($p, $words))          // matches with one word?
                {
                    return true;
                }
            }
        }
    }
    return false;   // Not found
    
//    else // Best by Levenshtein distance
//    {        
//        $dist = array();
//        $sdx = array();
//        foreach($players as $pla)
//        {
//            $dist[$pla] = levenshtein($p, $pla, 1, 3, 3);
//            $sdx[$pla] = soundex($pla);
//        }
//        asort($dist);
//        reset($dist);
//        $bestk = key($dist);
//        
//        print_r(array('Player' => $p, 'Distances' => $dist, 
//            'Soundex' => soundex($p), 'Soundexes' => $sdx));
//        
//        return ($dist[$bestk] < $THRESHOLD);
//    }
}

function clean_team_name($team)
{
    $team = ucwords(strtolower(trim($team)));
    $arr = explode(' ', $team);
    return $arr[0];
}

function clean_player_name($p, $isgazzetta = false)
{   
    $str = iconv("ISO-8859-1", "ASCII//TRANSLIT", trim($p));
    $str = preg_replace('/[^A-Za-z0-9\.\ ]/', '', strtolower($str));
    
    if($isgazzetta && ($pos = strpos($str, '.')) !== false)
    {
        $ss[0] = substr($str, $pos + 1, strlen($str) - $pos);
        $ss[1] = substr($str, 0, $pos);        
        $str = $ss[0] . ' ' . $ss[1] . '.'; 
    }
    return ucwords($str);
}

function startsWith($haystack, $needle)
{
    return $needle === "" || strpos($haystack, $needle) === 0;
}

function endsWith($haystack, $needle)
{
    return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
}
?>
