<?php 
//This file is meant to be called from the command line
require_once "settingsHydrogen.php";
require_once "Hydrogen/libDebug.php";
require_once "Hydrogen/clsDataSource.php";
require_once "clsDB.php";
require_once "caldav-client.php";
require_once "clsReminder.php";			
require_once "clsCalDAV.php";


echo "Synchronizing Calendars . . .";
CalDAV::PullCalendarUpdates();
echo "Done."
?>
