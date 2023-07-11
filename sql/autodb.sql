--
-- Table structure for table `autodb_prefs`
--

DROP TABLE IF EXISTS `autodb_prefs`;
CREATE TABLE `autodb_prefs` (
  `id` int(11) NOT NULL auto_increment,
  `dbtable` varchar(128) default NULL,
  `var` varchar(64) default NULL,
  `value` varchar(64) default NULL,
  `user` varchar(64) default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=119 DEFAULT CHARSET=latin1;

--
-- Table structure for table `autodb_rel`
--

DROP TABLE IF EXISTS `autodb_rel`;
CREATE TABLE `autodb_rel` (
  `adb_t1` varchar(128) NOT NULL default '',
  `adb_t1_relcol` varchar(128) NOT NULL default '',
  `adb_t2_remhost` varchar(255) default NULL,
  `adb_t2_rempass` varchar(64) default NULL,
  `adb_t2_remuser` varchar(64) default NULL,
  `adb_t2` varchar(128) NOT NULL default '',
  `adb_t2_relcol` varchar(128) NOT NULL default '',
  `adb_t2_dspcol` varchar(128) NOT NULL default '',
  PRIMARY KEY  (`adb_t1`,`adb_t1_relcol`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
