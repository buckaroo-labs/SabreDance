
CREATE TABLE `user` (
`id` INT NOT NULL AUTO_INCREMENT , 
  `username` varchar(50) NOT NULL,
  `email` varchar(99) NOT NULL,
  `password_hash` varchar(500)  NOT NULL COMMENT 'php password_hash(password,PASSWORD_BCRYPT)' ,
  `first_name` VARCHAR(30) NOT NULL ,
`last_name` VARCHAR(30) NOT NULL ,
  `reset_code` varchar(25) DEFAULT NULL,
  `session_id` varchar(50) DEFAULT NULL,
  `access_token` varchar(500) DEFAULT NULL,
  `last_ip` varchar(30) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `ins_user` varchar(30) NOT NULL DEFAULT 'system',
  `ins_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
    , PRIMARY KEY (`id`),
 UNIQUE KEY `username` (`username`),
UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reminders ( 
`id` INT NOT NULL AUTO_INCREMENT , 
`owner` VARCHAR(30) NOT NULL DEFAULT 'test' COMMENT 'user ID', 
`summary` VARCHAR(60) NOT NULL COMMENT 'title, e.g. feed cat', 
`location` VARCHAR(255) NULL  COMMENT 'free format', 
`url` VARCHAR(255) NULL COMMENT 'Any link useful for this reminder', 
`category` VARCHAR(500) NULL COMMENT 'comma-separated list of tags', 
`calendar_id` int NULL COMMENT 'foreign key ref to calendars table; blank=default-internal', 
`description` TEXT NULL  COMMENT 'Notes', 
`priority` INT NULL  , 
`recur_scale` INT NOT NULL DEFAULT '1' COMMENT 'hours,days,weeks,months,years = 0,1,2,3,4' , 
`recur_units` INT NULL COMMENT 'number of time units before reminder will recur quietly; null values indicate non-recurrence', 
`recur_float` INT NOT NULL DEFAULT '1' COMMENT 'recur after complete_date rather than after start_date; 0=false, other=true',
`grace_scale` INT NOT NULL DEFAULT '1' COMMENT 'hours,days,weeks,months,years = 0,1,2,3,4' , 
`grace_units` INT NULL  COMMENT 'amount of time after start_date before reminder will appear as overdue', 
`passive_scale` INT NOT NULL DEFAULT '1' COMMENT 'hours,days,weeks,months,years = 0,1,2,3,4' , 
`passive_units` INT NULL  COMMENT 'amount of time between start_date and alarm condition', 
`snooze_scale` INT NOT NULL DEFAULT '1' COMMENT 'hours,days,weeks,months,years = 0,1,2,3,4' , 
`snooze_units` INT NULL  COMMENT 'default amount of time to delay this reminder if user snoozes it', 
`alarm_interval_scale` INT NOT NULL DEFAULT '2' COMMENT 'hours,days,weeks,months,years = 0,1,2,3,4' , 
`alarm_interval_units` FLOAT NULL  COMMENT 'how often to send active reminders ', 
`alarm_sent_date` DATETIME NULL COMMENT 'date of last reminder sent',
`complete_date` DATETIME NULL COMMENT 'date last completed', 
`snooze_date` DATETIME NULL  COMMENT 'defer reminder until this date', 
`end_date` DATETIME NULL  COMMENT 'reminder will not recur after this date', 
`start_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'when this reminder will become current', 
`due_date` DATETIME NULL  COMMENT 'calculated based on start_date and days_grace', 
`active_date` DATETIME NULL COMMENT 'calculated based on start_date and days_passive', 
`days_of_week` CHAR(7)  NULL  COMMENT 'days of week this reminder is active: MTWtFSs; null implies all' , 
`season_start` INT  NULL COMMENT '0-364; days after January 1 that the season for this reminder starts'  , 
`season_end` INT NULL COMMENT '0-364; days after January 1 that the season for this reminder ends', 
`day_start` INT  NULL COMMENT '0-2359; military time for time of day that this reminder becomes active (mod 100 values over 59 will round down to 59)' ,
`day_end` INT  NULL COMMENT '0-2359; military time for time of day that this reminder becomes inactive (mod 100 values over 59 will round down to 59)'  , 
`last_modified` VARCHAR(25) NULL COMMENT 'date of last change to this record (as CalDAV string, GMT)', 
`prodid` VARCHAR(300) NULL  COMMENT 'used for CalDAV', 
`uid` VARCHAR(60) NULL  COMMENT 'used for CalDAV', 
`etag` VARCHAR(120) NULL  COMMENT 'used for CalDAV', 
`sequence` BIGINT NOT NULL  COMMENT 'used for preventing duplicate updates on page refresh', 
`created` VARCHAR(25) NULL COMMENT 'date created (as CalDAV string, GMT)', 
`caldav_hidden` VARCHAR(10240) NULL COMMENT 'unused CalDAV data', 
PRIMARY KEY (`id`), 
UNIQUE KEY(`UID`),
UNIQUE KEY(`sequence`),
INDEX `c_reminder_category_indx` (`calendar_id`), 
INDEX `c_reminder_owner_indx` (`owner`), 
INDEX `c_reminder_snooze_date_indx` (`snooze_date`),
INDEX `c_reminder_init_date_indx` (`complete_date`),
INDEX `c_reminder_end_date_indx` (`end_date`),
INDEX `c_reminder_start_date_indx` (`start_date`),
INDEX `c_reminder_due_date_indx` (`due_date`),
INDEX `c_reminder_active_date_indx` (`active_date`)
);
