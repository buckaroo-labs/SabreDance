# SabreDance
Setup and friendly GUI for [sabre/dav](https://github.com/sabre-io/dav)

Implements the instructions found at https://sabre.io/dav/install/ and https://sabre.io/dav/gettingstarted/ and includes corrections to their example code. 

Clone this repo or download the zip, then copy/move the files in it to the same path on your web server (your Base URI) where you intend to put sabre/dav. Browse to that path (or specifically to index.php) and you'll be guided from there. It will go easier if you choose '/dav/' as your Base URI (this is the default in settings.php).

See sabre/dav documentation for requirements (PHP, Composer, etc).

This tool currently requires MySQL (SQLite option supported by sabre may be included here later). Future version may also assist with Composer installation. Feel free to reach out for assistance.

Immediate development priorities are functionality for user addition, password/email changes, calendar and address book management.

Tested on PHP 8.1 and sabre/dav 3.2.0
