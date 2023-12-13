<?php
$settings['Timezone']='US/Eastern';
$settings['BaseURI']='/dav/';
$settings['Realm']='SabreDAV';

/*The following settings should be overridden by 'settingsDB.php', 
  and will be if the file exists and has this content.
  (Create this file using the lines below as template).
  The file 'settingsDB.php' is listed in .gitignore because 
  it should contain the password and doesn't belong in a repo. */
$settings['DBName']='sabredavdb';
$settings['DBUser']='username';
$settings['DBPass']='paSSw0rd';
$settings['DBHost']='127.0.0.1';
include('settingsDB.php');
?>
