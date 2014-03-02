<?php

include_once dirname(__FILE__) . "/engine/engine.php";

$wall = get_stream_content();

$html = format_stream_content($wall);

$next_datetime = next_sportday_time();

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
        <? @include("page_elements/scripts_updated.php"); ?>
        <link rel="stylesheet" type="text/css" href="<?= $baseUrl ?>javascript/county/css/county.css" />
        <script type="text/javascript" src="<?= $baseUrl ?>javascript/county/js/county.js"></script>
        <script type="text/javascript">

            var itemblock_length = 25;

            // Aggiornamento automatico wall
            setInterval(function() {
                var ts = $('#lastupdate').val();
                $.get('<?= $baseUrl ?>facebook/ajaxActions.php',
                       {'action' : 'update_stream', 'timestamp' : ts},
                       function(data) {
                           if(data != undefined && data != null) {
                                // Aggiorno timestamp ultimo aggiornamento
                                if(data['html'] != '' || data['comments'] != null)
                                    $('#lastupdate').val(data['timestamp']);

                                // Per tutti gli elementi, verificare che non esistano prima di inserirli
                                // Se esistono li elimino così da non inserirli nuovamente
                                var newarr= $.grep($(data['html']), function(el) {
                                    var item_id = $(el).attr('id');
                                    return ($('#' + item_id).length == 0);
                                });
                                $(newarr).each(function() {
                                    $('#sendpost').after($(this));
                                });

                                // Nuovi post interi all'inizio della pagina
                                //$('#sendpost').after(data['html']);
                                // Aggiungo i commenti ai relativi post o eventi
                                if(data['comments'] != undefined && data['comments'] != null) {
                                    for (var item in data['comments']) {
                                        var comm_id = $(data['comments'][item]).attr('id');
                                        // Inserisco il commento solo se non è già presente
                                        if($('#' + comm_id).length == 0)
                                            $('#' + item + ' .post_comments').append(data['comments'][item]);
                                    }
                                }
                           }
                       },
                       'json');
            }, 20000);

            $(document).ready(function() {

                // Gestisce il caricamento dei contenuti allo scroll della pagina
                $(function () {
                    var $win = $(window);

                    $win.scroll(function () {
                        if ($win.height() + $win.scrollTop() == $(document).height()) {
                            // Caricamento dinamico altri contenuti
                            $.post('<? //echo $baseUrl ?>facebook/ajaxActions.php',
                                    {action : 'get_older_post', start : itemblock_length},
                                    function(data) {
                                        if(data != null && data != 'ERRORE') {
                                            itemblock_length += itemblock_length;
                                            $('#wall').append(data);
                                        }
                                    },
                                    'html');
                        }
                    });
                });
            });
        </script>
        <script type="text/javascript">
            $(document).ready(function() {
                get_rss_feed();

                $('.block_head').click(function() {
                    toggleBlock($(this));
                    return false;
                }).next().hide();

                // Init the countdown timer
                var t = '<?= $next_datetime ?>'.split(/[- :]/);
                var d = new Date(t[0], t[1]-1, t[2], t[3], t[4], t[5]);
                
                $('#countdown').county({
                    endDateTime: d, 
                    reflection: false, 
                    animation: 'scroll', 
                    theme: 'black' });
            });

            function toggleBlock(block) {

                $(block).next().toggle('fast');
                $(block).toggleClass("block_head_normal");
                $(block).toggleClass("block_head_active");
            }

            function get_rss_feed() {
                $("#rss_content").empty();

                jQuery.get("<?php echo $baseUrl ?>rssBackend.php", {url : 'http://www.calciomercato.it/rss/'}, function(data) {
                    $('#loading_icon').css("display", "none");
                    $('.rss_small').css("height", "160px");
                    $('.rss_small').css("overflow", "scroll");
                    $('.rss_small').css("overflow-x", "hidden");
                    $(data).find('item').each(function() {
                        var $item = $(this);
                        var title = $item.find('title').text();
                        var link = $item.find('link').text();
                        var description = $item.find('description').text();
                        var pubDate = $item.find('pubDate').text();

                        var html = '<li><a href="' + link + '" target="_blank">' + title + '</a></li>';

                        $('#rss_content').append(html);
                    });
                });
            };
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
                <h2>FantaTorneo - The New Generation!</h2>


                <div>
                    <!-- News -->
                    <div class="block_head block_head_normal">
                        <span class="block_head_title">News - <small>Ultimissime da Calciomercato.it</small></span>
                        <img src="<?= $baseUrl ?>images/arrow.png" alt="arrow" class="block_head_title_icon"/>
                    </div>
                    <div id="news" class="block">
                        <div class="rss_small" style="width:100%; height: 160px; overflow: hidden;">
                            <img alt="Loading..." src="images/loading.gif" id="loading_icon" align="center" class="loading_icon"/>
                            <ul id="rss_content">

                            </ul>
                        </div>
                    </div>

                    <!-- Countdown -->
                    <div style="text-align: center;">
                        <div style="font-size: 14px; padding: 6px;">Prossima giornata: <span style="color: #0066FF;"><?= date('d/m/Y H:i', strtotime($next_datetime)) ?></span></div>
                        <div style="font-size: 14px; padding: 6px;">Inserisci la formazione! Hai ancora:</div>
                        <div id="countdown" style="margin-left: 158px;"></div>
                        <br/>
                    </div>

                    <!-- Wall -->
                    <div id="wall" style="width: 640px;" class="block">
                        <input type="hidden" id="lastupdate" name="lastupdate" value="<?= date('Y-m-d H:i:s', time()); ?>"/>

                        <?= $html ?>

                    </div>
                </div>
                
            </div>
        </div>
        <div id="footer">
            <p>Copyright (c) 2009 <a href="http://www.andreamartelli.it">Andrea Martelli</a>. All rights reserved.</p>
        </div>
    </body>
</html>
