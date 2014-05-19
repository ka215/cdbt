<?php
/*
  Plugin Name: Custom Database Tables
  Plugin URI: http://cdbt.ka2.org/
  Description: This plug-in allows you to perform data storage and reference by creating a free tables in database of WordPress.
  Version: 0.9.1
  Author: Katsuhiko Maeno
  Author URI: http://cdbt.ka2.org
  Copyright: 2014 monauralsound (email : ka2@ka2.org)
  License: GPL2 - http://www.gnu.org/licenses/gpl.txt
  Text Domain: custom-database-tables
  Domain Path: /langs
*/
define('PLUGIN_VERSION', '0.9.1');
define('PLUGIN_SLUG', 'custom-database-tables');

define('DS', DIRECTORY_SEPARATOR);
define('PLUGIN_DIR', dirname(__FILE__));
define('PLUGIN_LIB_DIR', PLUGIN_DIR . DS . 'lib');
define('PLUGIN_TMPL_DIR', PLUGIN_DIR . DS . 'templates');

require_once PLUGIN_LIB_DIR . DS . 'cdbt.class.php';
require_once PLUGIN_LIB_DIR . DS . 'shortcodes.php';
require_once PLUGIN_DIR . DS . 'functions.php';

global $cdbt;
$cdbt = new CustomDatabaseTables();

register_activation_hook(__FILE__, array($cdbt, 'activate'));
register_deactivation_hook(__FILE__, array($cdbt, 'deactivation'));
