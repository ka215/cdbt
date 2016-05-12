<?php
/**
 * Including libraries for this plugin
 */
defined('CDBT') OR die();

$library_dir_name = 'lib';
$library_dir = plugin_dir_path(__FILE__) . $library_dir_name;

$lib_includes = [];

$files = [
  'utils.php',         // Common utility class. Base class, and this class is available at single
  'core.php',         // Plugin core class
  'config.php',       // Configuration class
  'validate.php',     // Validate class for plugin inherited a common validator
  'db.php',            // Database class with wrapping wpdb
  'ajax.php',          // Trait for using ajax
  'tmpl.php',          // Trait for dynamic rendering templates
  'shortcodes/cdbt-view.php', 	// Trait of shortcode for "cdbt-view"
  'shortcodes/cdbt-entry.php', 	// Trait of shortcode for "cdbt-entry"
  'shortcodes/cdbt-edit.php', 	// Trait of shortcode for "cdbt-edit"
  'shortcodes.php', // Trait for shortcodes definitions
  'webapis.php',     // Trait for web apis definitions
  'extras.php',       // Trait for enhancements (for customization)
  'admin.php',       // Entry point class for admin panels
  'main.php',        // Entry point class for web frontend
  'init.php'          // Instance factory & plugin activater
];
foreach ($files as $file) {
  $lib_includes[] = $library_dir . '/' . $file;
}
unset($library_dir_name, $library_dir, $files, $file);

foreach ($lib_includes as $file) {
  if (!file_exists($file)) {
    trigger_error(sprintf(__('Include path error "%s"', CDBT), $file), E_USER_ERROR);
  }

  require_once $file;
}
unset($file);
