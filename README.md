# ![logo.png](logo.png)SabreDance

## Features
### Setup and friendly GUI for [sabre/dav](https://github.com/sabre-io/dav)

Implements the instructions found at https://sabre.io/dav/install/ and https://sabre.io/dav/gettingstarted/ and includes corrections to their example code. 

### Reminder recurrence management (coming soon)

A lack of standards for VTODO recurrence means that different clients will encode recurrence information differently, ignoring or even overwriting the recurrence information written by other clients. This tool will provide an HTTP interface for specifying recurrence and will make sure that the specification persists regardless of changes made by other clients.

## Requirements
See sabre/dav documentation for requirements (PHP, Composer, etc).

This tool currently requires MySQL (the SQLite option supported by sabre may be included here later). Future versions may also assist with Composer installation. 

Tested on PHP 8.1 and sabre/dav 3.2.0

## Instructions/Setup
Clone this repo or download the zip, then copy/move the files in it to the same path on your web server (your Base URI) where you intend to put sabre/dav. Browse to that path (or specifically to index.php) and you'll be guided from there. It will go easier if you choose '/dav/' as your Base URI (this is the default in settings.php). Once all the code is configured, admin.php is available for adding users. To modify users, for now you'll need to perform the updates directly in the database using a database client of some sort (mysql command line or phpMyAdmin).

To use the reminder management add-on, you'll also need to clone the [Hydrogen](https://github.com/buckaroo-labs/Hydrogen) repo (or download and unzip a copy) inside the folder where this tool sits. 

Feel free to reach out for assistance with this project, with sabre/dav in general, or even client setup (Thunderbird and iOS are working well for me).

## Future
Immediate development priorities are functionality for reminders, calendar and address book management.

