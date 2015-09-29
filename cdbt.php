<?php
/*
  Plugin Name: Custom DataBase Tables
  Plugin URI: http://ka2.org/
  Description: <strong>C</strong>ustom <strong>D</strong>ata<strong>B</strong>ase <strong>T</strong>ables is unleash the potential force of WordPress as the strongest CMS. It will dominate the MySQL database of WordPress, to create a freely table, and can be turning data thoroughly tinker.
  Version: 2.0.1
  Author: ka2
  Author URI: http://ka2.org
  Copyright: 2015 monauralsound (email : ka2@ka2.org)
  License: GPL2 - http://www.gnu.org/licenses/gpl.txt
  Text Domain: custom-database-tables
  Domain Path: /langs
*/
?>
<?php
/*  Copyright 2015 ka2 (http://ka2.org)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?>
<?php
define('CDBT_PLUGIN_VERSION', '2.0.1');
define('CDBT_DB_VERSION', '2.0');
define('CDBT', 'custom-database-tables'); // This plugin domain name

require_once plugin_dir_path(__FILE__) . 'functions.php';

CustomDatabaseTables\Lib\factory( 'set_global' );

