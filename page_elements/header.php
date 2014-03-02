<div id="header">
    <ul id="menu">
        <li><a href="<?echo $baseUrl?>index.php" title="Index">Home</a></li>
        <li><a href="<?echo $baseUrl?>rose.php" title="Rose">Rose</a></li>
        <li><a href="<?echo $baseUrl?>calendario.php" title="Calendario">Calendario</a></li>
        <li><a href="<?echo $baseUrl?>classifica.php" title="Classifica">Classifica</a></li>
        <li><a href="<?echo $baseUrl?>stat_campionato.php" title="Statistiche">Statistiche</a></li>
        <? if(!empty($_SESSION['tipo']) && $_SESSION[tipo]=='ADMIN') { ?>
        <li><a href="<?echo $baseUrl?>admin.php" title="Amministrazione">Amministrazione</a></li>
        <? }?>
        <? if(!empty($_SESSION['tipo']) && $_SESSION['tipo']=='MANAGER') { ?>
        <li><a href="<?echo $baseUrl?>manager.php" title="Gestione torneo">Gestione torneo</a></li>
        <? } ?>
        <? if(!empty($_SESSION['tipo']) && $_SESSION['tipo']!='ADMIN') { ?>
        <li><a href="<?echo $baseUrl?>formazione.php" title="Inserisci formazione">Inserisci formazione</a></li>
        <? } ?>
        <li><a href="<?echo $baseUrl?>news.php" title="News">News!</a></li>
    </ul>
</div>
