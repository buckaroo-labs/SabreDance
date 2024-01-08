<?php
require_once 'common.php';
require_once 'clsCalDAV.php';
require_once 'Hydrogen/clsDateTimeExt.php';
//In theory, the SQLBuilder class will sanitize 
//	all the POST variables used as column data

function calculate_weekdays() {
	global $sqlb;
	$daysOfWeek="";
	if (isset($_POST['MondayYN'])) $daysOfWeek .= "M";
	if (isset($_POST['TuesdayYN'])) $daysOfWeek .= "T";
	if (isset($_POST['WednesdayYN'])) $daysOfWeek .= "W";
	if (isset($_POST['ThursdayYN'])) $daysOfWeek .= "t";
	if (isset($_POST['FridayYN'])) $daysOfWeek .= "F";
	if (isset($_POST['SaturdayYN'])) $daysOfWeek .= "S";
	if (isset($_POST['SundayYN'])) $daysOfWeek .= "s";	
	if ($daysOfWeek!="") $sqlb->addColumn("days_of_week",$daysOfWeek);
	return 1;
}
function calculate_blackout_hours () {
	global $sqlb;
	if (isset($_POST['SilentHoursYN'])) {
		if (isset(_POST['TimeOfDayStart'])) {  
			$startTime=$_POST['TimeOfDayStart'];
			$startTime=str_replace($startTime,":","");
			$startTime=substr($startTime,1,4);
			$sqlb->addColumn("day_start",$startTime);
		}
		if (isset(_POST['TimeOfDayEnd'])) {  
			$endTime=$_POST['TimeOfDayEnd'];
			$endTime=str_replace($endTime,":","");
			$endTime=substr($endTime,1,4);
			$sqlb->addColumn("day_end",$endTime);
		}
	}
	return 1;
}

function calculate_seasonality() {
	global $sqlb;
	//UI year starts at day 1; DB year starts at day zero
	if (isset($_POST['SilentDaysYN'])) {
		if (isset(_POST['season_start'])) {  
			$startday= (int) $_POST['season_start'] -1;
			$sqlb->addColumn("day_start",$startday);
		}
		if (isset(_POST['season_end'])) {  
			$endday= (int) $_POST['season_end'] -1;
			$sqlb->addColumn("day_end",$endday);
		}
	}
	return 1;
}
function calculate_enddate() {
	global $sqlb;
	if (($_POST['EndDate'])!="")  {
		$endDate=$_POST['EndDate'];
			if (($_POST['EndTime'])!="") {
				$endDate .= " " . $_POST['EndTime'];
			}
		$sqlb->addColumn("end_date",$endDate);
	}
	return 1;
}		
		
function calculate_duedate() {
	global $sqlb;		
	global $startDate;
	$columns = array('grace_scale','grace_units');
	if (isset($_POST['GraceTime'])) {
		if($_POST['GraceTime']=="DueYN") {
			$sqlb->addVarColumns($columns);
			$interval ="+" . $_POST['grace_units'] . " " . decode_scale($_POST['grace_scale']);
			$dueDate = strtotime($interval,$startDate);
			$dueDateStr = date("Y-m-d H:i:s",$dueDate);
			$sqlb->addColumn("due_date",$dueDateStr);
		}
	} else {
		$sqlb->addNullColumn("grace_units");
	}
	return 1;
}
		
function calculate_alarms() {
	global $sqlb;
	global $startDate;
	
	$columns = array('passive_scale',					
					'passive_units',
					'alarm_interval_scale',
					'alarm_interval_units'
					);
	if (isset($_POST['Alarms'])){
		if($_POST['Alarms']=="AlarmYN") {
			$sqlb->addVarColumns($columns);
			$interval ="+" . $_POST['passive_units'] . " " . decode_scale($_POST['passive_scale']);
			$alarmDate = strtotime($interval,$startDate);	
			$almDateStr = date("Y-m-d H:i:s",$alarmDate);			
			$sqlb->addColumn("active_date",$almDateStr);
		} else {
			$sqlb->addNullColumn("passive_units");
		}
	} else {
		$sqlb->addNullColumn("passive_units");
	}
	return 1;
}	

function common_post_proc() {
	global $sqlb;
	global $dds;
	global $startDate;
	$sqlb->addColumn("owner",'principals/' . $_SESSION['username']);
	
	//process POST variables into sanitized SQL stmt
	
	//some are easy
	$columns = array('summary',
					'priority',
					'url',
					'location',					
					'category',	
					'snooze_scale',
					'snooze_units'					
					);
	$sqlb->addVarColumns($columns);
	
	//others require more processing
	$sqlb->addColumn("description",str_replace("\n",'\n',$_POST['description']));
	
	$startDateStr=$_POST['StartDate'] . " " . $_POST['StartTime'];
	$sqlb->addColumn("start_date",$startDateStr);
	$startDate= strtotime($startDateStr);	
	
	//Recurrence
	$columns = array('recur_scale','recur_units','recur_float');
	if (isset($_POST['Recurrence'])) {
		if($_POST['Recurrence']=="RecurYN") $sqlb->addVarColumns($columns);
	}
	
	$return=calculate_duedate();
	$return=calculate_alarms();
	$return=calculate_seasonality() ;
	$return=calculate_blackout_hours();
	$return=calculate_enddate();		
	$return=calculate_weekdays();
	
	//Cross your fingers
	$SQL=$sqlb->getSQL();
	$dds->setSQL($SQL);

}	
		
if ($_POST['ID']=="new") {
	$timestamp = (string) time();	
	$uid = CalDAV::uid();
	$sqlb = new SQLBuilder("INSERT");
	$sqlb->setTableName(DB::$reminder_table);

	$sqlb->addColumn("sequence",$timestamp);
	$sqlb->addColumn("created",DateTimeExt::zdate());
	$sqlb->addColumn("uid",$uid);
	if(isset($_POST['CALENDAR_ID'])) {
		if($_POST['CALENDAR_ID']!=0)	$sqlb->addColumn("calendar_id",$_POST['CALENDAR_ID']);
	}
	common_post_proc();
	$newID=$dds->getInt("select max(id) from " . DB::$reminder_table . " where owner='principals/" .  $_SESSION['username'] . "'");
	if(isset($_POST['CALENDAR_ID'])) {
		if($_POST['CALENDAR_ID']!=0)	{
			
			//this is handled in the CalDAV class now
			/*
			$sql = "insert into calendarobjects(uri,calendarid,componenttype,uid,size) ";
			//we'll update the size, calendardata, etag, and lastmodified later
			$sql .= " values('" . $uid . ".ics'," . $_POST['CALENDAR_ID'] . ",'VTODO','" . $uid . "',99)";
			$result = $dds->setSQL($sql);
			*/
			CalDAV::PushReminderUpdate($newID,true);
		}
	}

} else {
//not "new"	
	if (isset($_POST['DIRTY'])) {
		$sqlb = new SQLBuilder("UPDATE");
		$sqlb->setTableName(DB::$reminder_table);
		$sqlb->addWhere("id='" .  (int) $_POST['ID']. "'");
		$sqlb->addWhere("owner='principals/" .  $_SESSION['username']. "'");
		$sqlb->addColumn("last_modified",DateTimeExt::zdate());
		if(isset($_POST['CALENDAR_ID'])) {
			$tempID=$_POST['CALENDAR_ID'];
			if($_POST['CALENDAR_ID']==0)	$tempID='null';
			$sqlb->addColumn("calendar_id",$tempID);
		}
		common_post_proc();
		
		//For now, assumption is that any reminder having a (nonzero) calendar ID is part of a remote system
		//Maybe later we will support multiple local calendars
		//Not sure how this will work if the calendar ID is edited   ... something to test
		if(isset($_POST['CALENDAR_ID'])) {
			if(isset($_POST['OLD_CALENDAR_ID'])) {
				if($_POST['CALENDAR_ID']!=$_POST['OLD_CALENDAR_ID']) {
					//Need to remove the item from the old calendar
					CalDAV::DeleteReminderFromCalendar($_POST['ID'], $_POST['OLD_CALENDAR_ID']);
				}
			}
			if($_POST['CALENDAR_ID']!=0)	CalDAV::PushReminderUpdate($_POST['ID'],true);

		}
	} //dirty


} //not new

$reminderID = (int) $_POST['ID'];

?>
