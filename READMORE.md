#Documentation

##Features
### Reminder recurrence management 

A lack of standards for VTODO recurrence means that different clients will encode recurrence information differently, ignoring 
or even overwriting the recurrence information written by other clients. This tool will provide an HTTP interface for specifying recurrence 
and will make sure that the specification persists regardless of changes made by other clients.

* The "default" calendar in the Reminders module is not DAV-enabled. New reminders can be created in this or any of the calendars belonging to the user in sabre/dav,
  and reminders can be moved between calendars.
* The "alarms" section in the reminder-editing page has not been fully implemented.
