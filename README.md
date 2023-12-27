# ![SabreDance](title.png)

## Features
### Setup and friendly GUI for [sabre/dav](https://github.com/sabre-io/dav)

Implements the instructions found at https://sabre.io/dav/install/ and https://sabre.io/dav/gettingstarted/ and includes corrections to their example code. 

### Reminder recurrence management 

There's a reason that SabreDAV has yet to bundle any CalDAV/CardDAV client software with their server, and that's because it's compatible with many existing clients. So why do we bother here? The short answer is that a lack of standards among those clients sometimes leads to changes being lost, so our answer is to keep a duplicate copy of some of your data and give you an interface for managing that.

Specifically, a lack of standards for VTODO recurrence means that different clients will encode recurrence information differently, ignoring or even overwriting the recurrence information written by other clients. This tool will provide an HTTP interface for specifying recurrence and will make sure that the specification persists regardless of changes made by other clients. See more under [READMORE.md](READMORE.md)

## Requirements
See sabre/dav documentation for their requirements (PHP, Composer, etc). Requirements specific to this project include:

*The [Hydrogen](https://github.com/buckaroo-labs/Hydrogen) library (see easy instructions below).

*Due to the Hydrogen library's dependency on MySQL, this tool requires MySQL as a database (SQLite and other options are supported by sabre). 

Tested on PHP 8.1, sabre/dav 3.2.0, Hydrogen 1.0.3  

## Instructions/Setup
Clone this repo or download the zip, then copy/move the files in it to the same path on your web server (your Base URI) where you intend to put sabre/dav. Browse to that path (or specifically to index.php) and you'll be guided from there. It will go easier if you choose '/dav/' as your Base URI (this is the default in settings.php). 

You'll also need to clone the [Hydrogen](https://github.com/buckaroo-labs/Hydrogen) repo (or download and unzip a copy) inside the folder where this tool sits (I should probably make this a submodule so that happens automatically). See more under [READMORE.md](READMORE.md)

Once all the code is configured, admin.php is available for adding users. To modify users, for now you'll need to perform the updates directly in the database using a database client of some sort (mysql command line or phpMyAdmin).

Use the link on index.php to calendarserver.php to create new calendars under your username (e.g. https://yourdomain.com/dav/calendarserver.php > calendars > yourusername > Create new calendar). If you start creating reminders as a user having no calendar, your reminders will go into a list that is not DAV-enabled, but you can move them (editing them one by one) to a DAV calendar later on.

To enforce or synchronize changes in the reminders recurrence schedule between heterogenous clients, schedule a job that will run "php batch_processing.php"

Feel free to reach out for assistance with this project, with sabre/dav in general, or even client setup (Thunderbird and iOS are working well for me).

## Future
Immediate development priorities are functionality for reminders, calendar and address book management, user management.

