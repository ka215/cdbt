<?php
/**
 * Including libralies for this plugin
 */
defined('CDBT') OR die();

$libraly_dir_name = 'lib';
$libraly_dir = plugin_dir_path(__FILE__) . $libraly_dir_name;

$lib_includes = [];

$files = scandir($libraly_dir);
foreach ($files as $key => $value) {
  if (!in_array($value, [ '.', '..' ])) {
    if (!is_dir($libraly_dir . '/' . $value)) {
      if (preg_match('/^cdbt\..*$/iU', $value)) continue;
      $lib_includes[] = $libraly_dir . '/' . $value;
    }
  }
}
unset($libraly_dir_name, $libraly_dir, $files, $key, $value);

foreach ($lib_includes as $file) {
  if (!file_exists($file)) {
    trigger_error(sprintf(__('Error locating %s for inclusion', CDBT), $file), E_USER_ERROR);
  }

  require_once $file;
}
unset($file);
