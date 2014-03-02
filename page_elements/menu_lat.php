<div class="box">
    <h3>Menu Principale</h3>
    <ul class="bottom">
        <li class="first"><a href="<?echo $baseUrl?>index.php">Home</a></li>
        <li style="color: #0066FF; font-weight: bold;">Rose</li>
        <ul>
            <li><a href="<?echo $baseUrl?>rose.php">Visualizza tutte</a></li>
            <? if(!empty($_SESSION['tipo']) && $_SESSION['tipo']=='MANAGER') { ?>
            <li><a href="<?echo $baseUrl?>inserisci_rosa.php">Inserisci / modifica</a></li>
            <? } ?>
        </ul>        
        <li><a href="<?echo $baseUrl?>calendario.php">Calendario</a></li>
        <li><a href="<?echo $baseUrl?>classifica.php">Classifica</a></li>
        <li><a href="<?echo $baseUrl?>stat_campionato.php">Statistiche</a></li>
        <li><a href="<?echo $baseUrl?>elenco_giocatori.php">Elenco giocatori</a></li>
        <li style="color: #0066FF; font-weight: bold;">Formazioni</li>
        <ul>
            <? if(!empty($_SESSION['tipo']) && $_SESSION['tipo']!='ADMIN') { ?>
            <li><a href="<?echo $baseUrl?>formazione.php">Inserisci</a></li>
            <? } ?>
            <? if(!empty($_SESSION['tipo']) && $_SESSION['tipo']=='MANAGER') { ?>
            <li><a href="<?echo $baseUrl?>mod_formazioni.php">Modifica tutte</a></li>
            <? } ?>
            <li><a href="<?echo $baseUrl?>form_giornata.php">Formazioni di giornata</a></li>
            <li><a href="<?echo $baseUrl?>archivio_formazioni.php">Archivio</a></li>
        </ul>
        <? if(!empty($_SESSION['tipo']) && $_SESSION['tipo']=='MANAGER') { ?>
        <li><a href="<?echo $baseUrl?>manager.php">Gestione torneo</a></li>
        <? } ?>
        <li><a href="<?echo $baseUrl?>news.php">News</a></li>
    </ul>
</div>