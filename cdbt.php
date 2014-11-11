<?php
/*
  Plugin Name: Custom Database Tables
  Plugin URI: http://cdbt.ka2.org/
  Description: This plug-in allows you to perform data storage and reference by creating a free tables in database of WordPress.
  Version: 1.1.8
  Author: ka2
  Author URI: http://cdbt.ka2.org
  Copyright: 2014 monauralsound (email : ka2@ka2.org)
  License: GPL2 - http://www.gnu.org/licenses/gpl.txt
  Text Domain: custom-database-tables
  Domain Path: /langs
*/
define('CDBT_PLUGIN_VERSION', '1.1.8');
define('CDBT_DB_VERSION', (float)1.2);
define('CDBT_PLUGIN_SLUG', 'custom-database-tables');

define('CDBT_DS', DIRECTORY_SEPARATOR);
define('CDBT_PLUGIN_DIR', dirname(__FILE__));
define('CDBT_PLUGIN_LIB_DIR', CDBT_PLUGIN_DIR . CDBT_DS . 'lib');
define('CDBT_PLUGIN_TMPL_DIR', CDBT_PLUGIN_DIR . CDBT_DS . 'templates');

require_once CDBT_PLUGIN_LIB_DIR . CDBT_DS . 'cdbt.class.php';
require_once CDBT_PLUGIN_LIB_DIR . CDBT_DS . 'cdbt.ajax.php';
require_once CDBT_PLUGIN_LIB_DIR . CDBT_DS . 'cdbt.media.php';
require_once CDBT_PLUGIN_LIB_DIR . CDBT_DS . 'cdbt.scripts.php';
require_once CDBT_PLUGIN_LIB_DIR . CDBT_DS . 'cdbt.shortcodes.php';
require_once CDBT_PLUGIN_DIR . CDBT_DS . 'functions.php';

global $cdbt;
$cdbt = new CustomDatabaseTables();

register_activation_hook(__FILE__, array($cdbt, 'activate'));
register_deactivation_hook(__FILE__, array($cdbt, 'deactivation'));
