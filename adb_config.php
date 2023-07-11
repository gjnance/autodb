<?php
define('MYSQL_HOST', 'localhost');
define('MYSQL_USER', 'XXX');
define('MYSQL_PASS', 'XXX');

// AUTODB_TABLE should be a table that looks like the table 'autodb_tables' (below).
//define('AUTODB_REL', 'gnanceco_demo.autodb_rel');
define('AUTODB_REL', 'gnanceco_demo.autodb_rules');
define('AUTODB_PREFS', 'gnanceco_playground.autodb_prefs');
define('AUTODB_BASEURL', '/autodb');
define('AUTODB_DB', 'gnanceco_demo');

// CREATE TABLE autodb_rel (
//   adb_t1 VARCHAR(128) NOT NULL,
//   adb_t1_relcol VARCHAR(128) NOT NULL,
//   adb_t2 VARCHAR(128) NOT NULL,
//   adb_t2_relcol VARCHAR(128) NOT NULL,
//   adb_t2_dspcol VARCHAR(128) NOT NULL,
//   PRIMARY KEY (adb_t1, adb_t1_relcol)
// );
//
// Huh?
//
// Suppose the following row exists in a table called 'users':
//   +----+----------+----------+--------------+
//   | id | name     | username | user_type_id |
//   +----+----------+----------+--------------+
//   |  1 | Joe Cool | jcool    | 7            |
//   +----+----------+----------+--------------+
//
// And the column 'user_type_id' is a reference to an id in a table called 'user_types':
//   +----+----------------+--------+
//   | id | user_type_name | rights |
//   +----+----------------+--------+
//   |  7 | Administrator  | 755    |
//   +----+----------------+--------+
//
// Wouldn't it be cool if 'user_type_id' displayed as something useful instead of '7'?
// Here's an 'autodb_tables' entry that will do just that:
//  +-------------+---------------+-----------------+---------------+----------------+
//  | adb_t1      | adb_t1_relcol | adb_t2          | adb_t2_relcol | adb_t2_dspcol  |
//  +-------------+---------------+-----------------+---------------+----------------+
//  | <db>.users  | user_type_id  | <db>.user_types | id            | user_type_name |
//  +-------------+---------------+-----------------+---------------+----------------+
//
// NOTE: <db> should be changed here to the database containing these two tables
?>
