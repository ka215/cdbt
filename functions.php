<?php
/**
 * Including libralies for this plugin
 */
defined('CDBT') OR die();

$libraly_dir_name = 'lib';
$libraly_dir = plugin_dir_path(__FILE__) . $libraly_dir_name;

$lib_includes = [];

$files = [
  'utils.php',     // Common utility class. Base class, and this class is available at single
  'core.php',     // Plugin core class
  'config.php',   // Configuration class
  'db.php',        // Database class with wrapping wpdb
  'tmpl.php',      // Trait for dynamic rendering templates
  'extras.php',   // Trait for enhancements (for customization)
  'main.php',     // Entry point class for web frontend
  'admin.php',    // Entry point class for admin panels
  'init.php'        // Instance factory & plugin activater
];
foreach ($files as $file) {
  $lib_includes[] = $libraly_dir . '/' . $file;
}
unset($libraly_dir_name, $libraly_dir, $files, $file);

foreach ($lib_includes as $file) {
  if (!file_exists($file)) {
    trigger_error(sprintf(__('Error locating %s for inclusion', CDBT), $file), E_USER_ERROR);
  }

  require_once $file;
}
unset($file);
