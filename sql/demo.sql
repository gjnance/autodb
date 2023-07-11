CREATE TABLE contacts (
  id int(11) NOT NULL auto_increment,
  name_last varchar(128) NOT NULL default '',
  name_first varchar(128) NOT NULL default '',
  phone_mobile varchar(32) default '',
  phone_home varchar(32) default '',
  phone_work varchar(32) default '',
  phone_fax varchar(32) default NULL,
  street tinytext,
  locality varchar(64) default NULL,
  region varchar(64) default NULL,
  postcode varchar(16) default NULL,
  country_id int(11) default NULL,
  email1 varchar(128) default '',
  email2 varchar(128) default '',
  birthdate date default NULL,
  notes mediumtext,
  export int(11) default '1',
  export2 int(11) default '1',
  export3 int(11) default NULL,
  PRIMARY KEY  (id)
) ENGINE=MyISAM AUTO_INCREMENT=167 DEFAULT CHARSET=latin1;

--
-- Table structure for table countries
--

DROP TABLE IF EXISTS countries;
CREATE TABLE countries (
  id int(11) NOT NULL auto_increment,
  name varchar(255) default NULL,
  code varchar(16) default NULL,
  PRIMARY KEY  (id)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;
