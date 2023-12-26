# ![logo.png](logo.png)SabreDance

## Features
### Setup and friendly GUI for [sabre/dav](https://github.com/sabre-io/dav)

Implements the instructions found at https://sabre.io/dav/install/ and https://sabre.io/dav/gettingstarted/ and includes corrections to their example code. 

### Reminder recurrence management 

A lack of standards for VTODO recurrence means that different clients will encode recurrence information differently, ignoring or even overwriting the recurrence information written by other clients. This tool will provide an HTTP interface for specifying recurrence and will make sure that the specification persists regardless of changes made by other clients.

* The "default" calendar in the Reminders module is not DAV-enabled. New reminders can be created in this or any of the calendars belonging to the user in sabre/dav, and reminders can be moved between calendars.
* The "alarms" section in the reminder-editing page has not been fully implemented.

## Requirements
See sabre/dav documentation for their requirements (PHP, Composer, etc). Requirements specific to this project include:

*The [Hydrogen](https://github.com/buckaroo-labs/Hydrogen) library (see easy instructions below).
*Due to the Hydrogen library's dependency on MySQL, this tool requires MySQL as a database (SQLite and other options are supported by sabre). 

Tested on PHP 8.1, sabre/dav 3.2.0, Hydrogen 1.0  

## Instructions/Setup
Clone this repo or download the zip, then copy/move the files in it to the same path on your web server (your Base URI) where you intend to put sabre/dav. Browse to that path (or specifically to index.php) and you'll be guided from there. It will go easier if you choose '/dav/' as your Base URI (this is the default in settings.php). 

You'll also need to clone the [Hydrogen](https://github.com/buckaroo-labs/Hydrogen) repo (or download and unzip a copy) inside the folder where this tool sits (I should probably make this a submodule so that happens automatically):
* wget https://github.com/buckaroo-labs/Hydrogen/archive/refs/tags/v1.0.tar.gz
* gunzip v1.0.tar.gz
* tar -xvf v1.0.tar
* ln -s Hydrogen-1.0 Hydrogen

Once all the code is configured, admin.php is available for adding users. To modify users, for now you'll need to perform the updates directly in the database using a database client of some sort (mysql command line or phpMyAdmin).

Use the link on index.php to calendarserver.php to create new calendars under your username (e.g. https://yourdomain.com/dav/calendarserver.php > calendars > yourusername > Create new calendar).

To enforce or synchronize changes in the reminders recurrence schedule between heterogenous clients, schedule a job that will run "php batch_processing.php"

Feel free to reach out for assistance with this project, with sabre/dav in general, or even client setup (Thunderbird and iOS are working well for me).

## Future
Immediate development priorities are functionality for reminders, calendar and address book management.

