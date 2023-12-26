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

$settings['color1']="w3-black";
$settings['color2']="w3-red";
$settings['color3']="w3-hover-white";
$settings['color4']="w3-hover-black";


$navbar_links[0]=array("name"=>'<img src="logo.png" height="16">',"href"=>"admin.php","class"=>"w3-theme-l1");
$navbar_links[1]=array("name"=>"Home","href"=>"index.php","class"=>"w3-hide-small " . $settings['color3']);
//$navbar_links[2]=array("name"=>"About","href"=>"index.php","class"=>"w3-hide-small " . $settings['color3']);
//$navbar_links[2]=array("name"=>"Demo","href"=>"demo.php","class"=>"w3-hide-small " . $settings['color3']);


$sidebar_links[0]=array("name"=>"Reminders","href"=>"reminders.php","class"=>$settings['color4']);
$sidebar_links[1]=array("name"=>"New Reminder","href"=>"edit_reminder.php?ID=new","class"=>$settings['color4']);
//$sidebar_links[2]=array("name"=>"Demo","href"=>"demo.php","class"=>"w3-hide-large w3-hide-medium " . $settings['color4']);
//$sidebar_links[3]=array("name"=>"Lists","href"=>"calendars.php","class"=>$settings['color4']);
//$sidebar_links[3]=array("name"=>"CalDAV Setup","href"=>"caldav_accounts.php","class"=>$settings['color4']);

$settings['footer_text1']="<p><a target="_blank" href="https://sabre.io/dav/">SabreDAV</a>maintained by fruux</p>";
$settings['footer_text2']='<p><a target="_blank" href="https://github.com/buckaroo-labs/SabreDance">SabreDance</a> additions by <a target="_blank" href="https://github.com/buckaroo-labs">buckaroo-labs</a><p>';

$settings['prompt_reg']='0';
$hideSearchForm = true;
$settings['DEBUG']=false;
$settings['login_page']='login.php';
?>
