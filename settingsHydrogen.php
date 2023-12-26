<?php
require_once('settings.php');

//get the following from settings and set them as Hydrogen-standard variables
/*
$settings['DBName']='sabredavdb';
$settings['DBUser']='username';
$settings['DBPass']='paSSw0rd';
$settings['DBHost']='127.0.0.1';
 */
if (!isset($settings['DEFAULT_DB_TYPE'])) $settings['DEFAULT_DB_TYPE'] = "mysql";
if (!isset($settings['DEFAULT_DB_INST'])) $settings['DEFAULT_DB_INST'] = $settings['DBName'];
if (!isset($settings['DEFAULT_DB_USER'])) $settings['DEFAULT_DB_USER'] = $settings['DBUser'];
if (!isset($settings['DEFAULT_DB_PASS'])) $settings['DEFAULT_DB_PASS'] = $settings['DBPass'];
if (!isset($settings['DEFAULT_DB_HOST'])) $settings['DEFAULT_DB_HOST'] = $settings['DBHost'];

$logo_image="logo.png";

$navbar_links[0]=array("name"=>'<img src="logo.png" height="16">',"href"=>"admin.php","class"=>"w3-theme-l1");
$navbar_links[1]=array("name"=>"Home","href"=>"index.php","class"=>"w3-hide-small w3-hover-white");
//$navbar_links[2]=array("name"=>"About","href"=>"index.php","class"=>"w3-hide-small w3-hover-white");
//$navbar_links[2]=array("name"=>"Demo","href"=>"demo.php","class"=>"w3-hide-small w3-hover-white");


$sidebar_links[0]=array("name"=>"Reminders","href"=>"reminders.php","class"=>"w3-hover-black");
$sidebar_links[1]=array("name"=>"New Reminder","href"=>"edit_reminder.php?ID=new","class"=>"w3-hover-black");
//$sidebar_links[2]=array("name"=>"Demo","href"=>"demo.php","class"=>"w3-hide-large w3-hide-medium w3-hover-white");
//$sidebar_links[3]=array("name"=>"Lists","href"=>"calendars.php","class"=>"w3-hover-black");
//$sidebar_links[3]=array("name"=>"CalDAV Setup","href"=>"caldav_accounts.php","class"=>"w3-hover-black");

$footer_text="This page was generated at " . date("Y-m-d H:i:s");

$settings['prompt_reg']='0';
$hideSearchForm = true;
$settings['DEBUG']=true;
$settings['login_page']='login.php';
?>
