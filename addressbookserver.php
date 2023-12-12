<?php
require 'settings.php';
date_default_timezone_set($settings['Timezone']);

// Make sure this setting is turned on and reflect the root url for your WebDAV server.
// This can be for example the root / or a complete path to your server script
$baseUri = $settings['BaseURI'] . 'addressbookserver.php';

/* Database */
$pdo = new \PDO('mysql:dbname=' . $settings['DBName'] . ';host=' . $settings['DBHost]' , $settings['DBUser]' , $settings['DBPass]' );
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Autoloader
require_once 'vendor/autoload.php';

// Backends
$authBackend = new Sabre\DAV\Auth\Backend\PDO($pdo);
$principalBackend = new Sabre\DAVACL\PrincipalBackend\PDO($pdo);
$carddavBackend = new Sabre\CardDAV\Backend\PDO($pdo);
$authBackend->setRealm($settings['Realm']);

// Setting up the directory tree //
$nodes = [
    new Sabre\DAVACL\PrincipalCollection($principalBackend),
//    new Sabre\CalDAV\CalendarRoot($authBackend, $caldavBackend),
    new Sabre\CardDAV\AddressBookRoot($principalBackend, $carddavBackend),
];

// The object tree needs in turn to be passed to the server class
$server = new Sabre\DAV\Server($nodes);
$server->setBaseUri($baseUri);

// Plugins
$server->addPlugin(new Sabre\DAV\Auth\Plugin($authBackend));
$server->addPlugin(new Sabre\DAV\Browser\Plugin());
//$server->addPlugin(new Sabre\CalDAV\Plugin());
$server->addPlugin(new Sabre\CardDAV\Plugin());
$server->addPlugin(new Sabre\DAVACL\Plugin());
$server->addPlugin(new Sabre\DAV\Sync\Plugin());

// And off we go!
$server->exec();
