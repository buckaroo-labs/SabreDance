# Documentation

## Features
### Reminder recurrence management 

A lack of standards for VTODO recurrence means that different clients will encode recurrence information differently, ignoring 
or even overwriting the recurrence information written by other clients. This tool will provide an HTTP interface for specifying recurrence 
and will make sure that the specification persists regardless of changes made by other clients.

* The "default" calendar in the Reminders module is not DAV-enabled. New reminders can be created in this or any of the calendars belonging to the user in sabre/dav,
  and reminders can be moved between calendars.
* The "alarms" section in the reminder-editing page has not been fully implemented.
## Setup

You'll need to clone the [Hydrogen](https://github.com/buckaroo-labs/Hydrogen) repo (or download and unzip a copy) inside the folder where this tool sits: 

* wget [https://github.com/buckaroo-labs/Hydrogen/archive/refs/tags/v1.0.3.tar.gz](https://github.com/buckaroo-labs/Hydrogen/archive/refs/tags/v1.0.3.tar.gz)
* gunzip v1.0.3.tar.gz
* tar -xvf v1.0.3.tar
* ln -s Hydrogen-1.0.3 Hydrogen
