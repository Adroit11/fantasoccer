--
-- Struttura della tabella `calendario`
--

CREATE TABLE IF NOT EXISTS `calendario` (
  `giornata` int(11) NOT NULL DEFAULT '0',
  `squadra1` varchar(50) NOT NULL DEFAULT '',
  `squadra2` varchar(50) NOT NULL DEFAULT '',
  `punti1` float DEFAULT NULL,
  `punti2` float DEFAULT NULL,
  `gol1` int(11) DEFAULT NULL,
  `gol2` int(11) DEFAULT NULL,
  `risultato1` enum('V','P','S') DEFAULT NULL,
  `risultato2` enum('V','P','S') DEFAULT NULL,
  PRIMARY KEY (`giornata`,`squadra1`,`squadra2`),
  KEY `cal_sq1` (`squadra1`),
  KEY `cal_sq2` (`squadra2`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Struttura della tabella `commenti`
--

CREATE TABLE IF NOT EXISTS `commenti` (
  `id` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `testo` longtext,
  `user` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Struttura della tabella `formazioni`
--

CREATE TABLE IF NOT EXISTS `formazioni` (
  `giornata` int(11) NOT NULL DEFAULT '0',
  `squadra` varchar(50) NOT NULL DEFAULT '',
  `nome` varchar(30) NOT NULL DEFAULT '',
  `cognome` varchar(30) DEFAULT NULL,
  `tipo` enum('TITOLARE','PRIMA_RISERVA','SECONDA_RISERVA') NOT NULL DEFAULT 'TITOLARE',
  `orario` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`giornata`,`squadra`,`nome`),
  KEY `form_sq` (`squadra`),
  KEY `form_gioc` (`nome`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Struttura della tabella `formazioni_temp`
--

CREATE TABLE IF NOT EXISTS `formazioni_temp` (
  `squadra` varchar(50) NOT NULL DEFAULT '',
  `nome` varchar(30) NOT NULL DEFAULT '',
  `cognome` varchar(30) DEFAULT NULL,
  `tipo` enum('TITOLARE','PRIMA_RISERVA','SECONDA_RISERVA') NOT NULL DEFAULT 'TITOLARE',
  `orario` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`orario`,`squadra`,`nome`),
  KEY `form_sq` (`squadra`),
  KEY `form_gioc` (`nome`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
--
-- Struttura della tabella `giocatori`
--

CREATE TABLE IF NOT EXISTS `giocatori` (
  `nome` varchar(30) NOT NULL DEFAULT '',
  `cognome` varchar(30) DEFAULT NULL,
  `squadra` varchar(50) DEFAULT NULL,
  `seriea` varchar(50) DEFAULT NULL,
  `valore` int(11) DEFAULT NULL,
  `ruolo` enum('P','D','C','A') NOT NULL DEFAULT 'P',
  PRIMARY KEY (`nome`),
  KEY `gioc_sq` (`squadra`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Struttura della tabella `giornate`
--

CREATE TABLE IF NOT EXISTS `giornate` (
  `n` int(11) NOT NULL DEFAULT '0',
  `torneo` tinyint(1) DEFAULT '0',
  `giocata` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`n`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Struttura della tabella `squadre`
--

CREATE TABLE IF NOT EXISTS `squadre` (
  `nome` varchar(50) NOT NULL DEFAULT '',
  `presidente` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`nome`),
  KEY `sq_ut` (`presidente`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Struttura della tabella `stream`
--

CREATE TABLE IF NOT EXISTS `stream` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user` varchar(30) NOT NULL DEFAULT '',
  `timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `type` enum('POST','EVENT','COMMENT') NOT NULL DEFAULT 'POST',
  `subtype` enum('FORM_INS','FORM_MOD','RESULTS_PUB','PLAYERS','TEAM_CREATE','TEAM_MOD') DEFAULT NULL,
  `content` text,
  `reference` int(10) unsigned DEFAULT NULL,
  `giornata` int(11) DEFAULT NULL,
  `object` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1033 ;

--
-- Struttura della tabella `utenti`
--

CREATE TABLE IF NOT EXISTS `utenti` (
  `username` varchar(30) NOT NULL DEFAULT '',
  `pwd` varchar(32) NOT NULL DEFAULT '',
  `tipo` enum('ADMIN','MANAGER','USER') NOT NULL DEFAULT 'ADMIN',
  `fb_uid` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dump dei dati per la tabella `utenti`
--

INSERT INTO `utenti` (`username`, `pwd`, `tipo`, `fb_uid`) VALUES
('fabiogiuseppe', 'f9f2e0c1a4a56e5460ac71a5f5a4a313', 'USER', NULL),
('andrew', 'b750ed7be6360c85312054a26ede8aaa', 'MANAGER', NULL),
('fabiolamberti', '47bc29db5232735f9bc972853167216c', 'USER', NULL),
('caizka', 'd823ba481974f6ca8d0ce028fe355507', 'USER', NULL),
('marco', '38eadc0564c66fcd6fc201bf5520cf6a', 'MANAGER', NULL),
('max', 'b00faaf16ad158f9a0be547bd362563c', 'USER', NULL),
('dario', '081ccdc77aacbd45817407cee9d60f84', 'USER', NULL),
('nicola', 'b46f011059277f2ee532f74a2604e118', 'USER', NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `voti`
--

CREATE TABLE IF NOT EXISTS `voti` (
  `giornata` int(11) NOT NULL DEFAULT '0',
  `nome` varchar(30) NOT NULL DEFAULT '',
  `cognome` varchar(30) DEFAULT NULL,
  `voto` float DEFAULT NULL,
  `bonusmalus` float DEFAULT NULL,
  `gs` int(11) DEFAULT '0',
  `gf` int(11) DEFAULT '0',
  `autogol` int(11) DEFAULT '0',
  `gdv` int(11) DEFAULT '0',
  `gdp` int(11) DEFAULT '0',
  `ammonizioni` int(11) DEFAULT '0',
  `espulsioni` int(11) DEFAULT '0',
  `assist` int(11) DEFAULT '0',
  `rigsba` int(11) DEFAULT '0',
  `rigpar` int(11) DEFAULT '0',
  `titolare` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`giornata`,`nome`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;