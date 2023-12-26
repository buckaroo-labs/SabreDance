<?php
// https://sabre.io/dav/gettingstarted/
use Sabre\DAV;
require 'vendor/autoload.php';
require 'settings.php';

$rootDirectory = new DAV\FS\Directory('public');
$server = new DAV\Server($rootDirectory);
$server->setBaseUri($settings['BaseURI'] . 'server.php');
$lockBackend = new DAV\Locks\Backend\File('data/locks');
$lockPlugin = new DAV\Locks\Plugin($lockBackend);
$server->addPlugin($lockPlugin);
$server->addPlugin(new DAV\Browser\Plugin());

use Sabre\DAV\Auth;
$pdo = new \PDO('mysql:dbname=' . $settings['DBName'] . ';host=' . $settings['DBHost'] , $settings['DBUser'] , $settings['DBPass'] );
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

$authBackend = new Auth\Backend\PDO($pdo);
$authBackend->setRealm( $settings['Realm'] );
$authPlugin = new Auth\Plugin($authBackend);
$server->addPlugin($authPlugin);
$server->exec();
?>

