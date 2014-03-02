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
        <title>FantaTorneo *New Generation*</title>
        <? @include("page_elements/scripts.php"); ?>
        <link rel="shortcut icon" href="pics/favicon.ico"/>
        <script type="text/javascript">
            $(document).ready(function() {
                get_rss_feed();
            });

            function get_rss_feed() {
                $("#rss_content").empty();

                jQuery.get("<?php echo $baseUrl ?>rssBackend.php", {url : 'http://www.calciomercato.it/rss/'}, function(data) {
                    $('#loading_icon').hide("fast");
//                    $('.rss_small').css("height", "160px");
//                    $('.rss_small').css("overflow", "scroll");
//                    $('.rss_small').css("overflow-x", "hidden");
                    $(data).find('item').each(function() {
                        var $item = $(this);
			var title = $item.find('title').text();
			var link = $item.find('link').text();
			var description = $item.find('description').text();
			var pubDate = $item.find('pubDate').text();

                        var html = '<li><a href="' + link + '" target="_blank">' + title + '</a><br/>' + description + '</li>';

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
                <h2>Warm news</h2>
                <div class="rss" style="width:100%;">
                        <img alt="Loading..." src="images/loading.gif" id="loading_icon" align="center" class="loading_icon"/>
                        <ul id="rss_content">

                        </ul>
                </div>
            </div>
        </div>
        <br/><br/>
        <div id="footer">
            <p>Copyright (c) 2009 <a href="http://www.andreamartelli.it">Andrea Martelli</a>. All rights reserved.</p>
        </div>
    </body>
</html>
