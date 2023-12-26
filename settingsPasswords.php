<?php
//this logic is handled in the settingsHydrogen.php file also, 
//but this file's existence will suppress some warnings.
if (!isset($settings['DEFAULT_DB_TYPE'])) $settings['DEFAULT_DB_TYPE'] = "mysql";
if (!isset($settings['DEFAULT_DB_INST'])) $settings['DEFAULT_DB_INST'] = $settings['DBName'];
if (!isset($settings['DEFAULT_DB_USER'])) $settings['DEFAULT_DB_USER'] = $settings['DBUser'];
if (!isset($settings['DEFAULT_DB_PASS'])) $settings['DEFAULT_DB_PASS'] = $settings['DBPass'];
if (!isset($settings['DEFAULT_DB_HOST'])) $settings['DEFAULT_DB_HOST'] = $settings['DBHost'];

?>
