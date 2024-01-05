<?php
require_once ("Hydrogen/libDebug.php");
$debug[basename(__FILE__)]=true;
class CalDAV {
	public static function uid() {
		/** Get a random string
		* @return string 32 hexadecimal characters
		*/
		return md5(uniqid(mt_rand(), true));
	}

	public static function PushAllReminders() {
		global $dds;
		$sql="SELECT id from " . DB::$reminder_table . " WHERE calendar_id is not null order by id";
		$dds->setSQL($sql);
		$keys=$dds->getDataset();
		for ($i=0; $i < count($keys); $i++) {
			CalDAV::PushReminderUpdate($keys[$i][0],true);
		}
	}

	public static function DeleteReminderFromCalendar($reminder_id,$etag){
		//This is used when a reminder is moved from one calendar to another.
		global $dds;
		require_once ("clsReminder.php");

		$rsql="SELECT r.etag as etag, r.uid as rem_uid, r.owner as owner ";
		$rsql.=" FROM " . DB::$reminder_table . " r ";
		$rsql.=" WHERE r.id =" . $reminder_id . " and etag='" . $etag . "'"	;

		$result=$dds->setSQL($rsql);
		debug (__FILE__ . ": DeleteReminder: SQL=" . $csql,basename(__FILE__));

		if ($rrow = $dds->getNextRow("labelled")) {
			$sql = "DELETE FROM " . DB::$reminder_table . " r ";
			$sql.=" WHERE r.id =" . $reminder_id ;
			$result=$dds->setSQL($sql);
			debug (__FILE__ . ": DeleteReminder: Data DELETED with return code of " . $resultCode,basename(__FILE__));
		}
	}

	public static function DoPUTRequest( $calendarID, $rem_uid, $icalendar, $etag = null ) {
	  //replace the calendardata BLOB, set a new etag, and return it
	  /*A future version of the Hydrogen library will support mysqli_real_escape_string, but for now
		we're going to make do with this:*/
	  global $settings;
  	  $mysqli= new mysqli($settings['DEFAULT_DB_HOST'], $settings['DEFAULT_DB_USER'], $settings['DEFAULT_DB_PASS'], 
			      $settings['DEFAULT_DB_INST']);
	  	
	  global $dds;
	  $newetag=md5(uniqid(mt_rand(), true));

	  $dateNow = new DateTimeImmutable();
	  $sql="UPDATE calendarobjects SET calendardata='" . $mysqli->real_escape_string($icalendar) . "', etag='" . $newetag 
		  . "' , lastmodified=" . $dateNow->getTimestamp() 
		  . " where calendarid=" . $calendarID . " and uid='" . $rem_uid . "' ";
	  if (isset($etag)) $sql.=" and etag='" . $etag . "'";
  	  $dds->setSQL($sql);
	  return $newetag;
	}

	public static function PushReminderUpdate($id, $force=false){
		global $dds;

		require_once ("clsReminder.php");
		$sql="SELECT r.calendar_id, r.etag as etag, r.uid as rem_uid, r.owner as owner ";
		$sql.=" FROM " . DB::$reminder_table . " r ";
		$sql.=" WHERE r.id =" . $id	;
		debug (__FILE__ . ": PushReminderUpdate: SQL=" . $sql,basename(__FILE__));
		$dds->setSQL($sql);
		debug (__FILE__ . ": PushReminderUpdate: SQL executed",basename(__FILE__));
		if ($result_row = $dds->getNextRow("labelled")) {
			$reminder = new Reminder($id,$result_row["owner"]);
			$icalendar = $reminder->serialize();
			debug (__FILE__ . ": PushReminderUpdate: ICS formatted output: \n" . $icalendar . "\n",basename(__FILE__));
			if (!$force) {
				$newETAG = $this->DoPUTRequest($result_row["calendar_id"] , $result_row["rem_uid"] , $icalendar, '"' .$result_row["etag"] . '"');
			} else {
				//force update
				debug (__FILE__ . ": PushReminderUpdate: target URL: " . $result_row["calendar_id"] . "/" . $result_row["rem_uid"] ,basename(__FILE__) );
				$newETAG = CalDAV::DoPUTRequest($result_row["calendar_id"] , $result_row["rem_uid"] , $icalendar);
			}
			debug (__FILE__ . ": PushReminderUpdate: Data PUT with return eTag of " . $newETAG,basename(__FILE__));
			if (strpos($newETAG,'HTTP/1.0 4')===false) {
				$sql = "UPDATE " . DB::$reminder_table . " SET etag='" . $newETAG . "' WHERE id=" . $id;
				$dds->setSQL($sql);
				debug (__FILE__ . ": PushReminderUpdate: ETAG updated in database",basename(__FILE__));
				return true;
			}	else {
				debug (__FILE__ . ": PushReminderUpdate: Bad request",basename(__FILE__));
				return false;
			}
		} // else : this is not a reminder with a remote calendar
	}

	public static function _LoadCalendarReminders($calDAVClient,$CalendarUID) {
		global $dds;
		//To be used synchronously with user logged in, when a CalDAV calendar is first added
		$sql="SELECT id from " . DB::$caldav_cal_table . " WHERE uid='" . $CalendarUID . "'";
		$dds->setSQL($sql);
		if ($result_row=$dds->GetNextRow()) $calendar_id=$result_row[0];
		$events=CalDAV::GetReminders($calDAVClient,$CalendarUID);
		$j=rand(0,1000000000);
		foreach ( $events as $k => $event ) {
			debug ("LoadCalendarReminders: loading event: \n" . $event['data'] . "\n",basename(__FILE__));
			$j = $j + 1;
			$parsed=CalDAV::parseEvent($event['data']);
			$S=CalDAV::getSQLBuilder($parsed);
			$S->addColumn("etag",$event['etag']);
			//$S->addColumn("url",$event['href']);
			$S->addColumn("calendar_id",$calendar_id);
			$S->addColumn("sequence",(string) $j);
			$dds->setSQL($S->getSQL());
		}
	}

	public static function PullCalendarUpdates() {
		/*
			To be run as background process. All these "requires" need to be done in the calling script
			To make the variables set in them be global.


		require_once "clsDB.php";
		require_once "settingsHydrogen.php";
		require_once "settingsPasswords.php";
		require_once "Hydrogen/clsDataSource.php";
		require_once ("clsReminder.php");
		*/
		global $dds;
		debug("PullCalendarUpdates: Reading owners",basename(__FILE__));
		$sql="SELECT distinct principaluri from " . DB::$caldav_cal_table ;
		$dds->setSQL($sql);
		$owners = $dds->GetDataset();
		for($x = 0; $x < count($owners); $x++) {
			debug("PullCalendarUpdates: 	Reading calendars for owner ". $owners[$x][0],"0/".basename(__FILE__));
			$sql="SELECT calendarid, uri from " . DB::$caldav_cal_table . " WHERE principaluri='" . $owners[$x][0] . "'";
			$dds->setSQL($sql);
			$calendars = $dds->GetDataset("labelled");
			for($y = 0; $y < count($calendars); $y++) {
				debug("PullCalendarUpdates: Reading calendar " . $calendars[$y]['uri'],basename(__FILE__));
				//we are reusing code here that was designed to talk to a CalDAV server
				//  rather than to its database directly
				//$calDAVClient = new CalDAVClient( "dummy", "dummy", "dummy", "dummy" );

				//pull all the reminders for this calendar (array of id,etag,uri,calendardata)
				$etags=CalDAV::GetReminders($calendars[$y]['calendarid']);

				$sql="SELECT etag, summary from " . DB::$reminder_table . " WHERE calendar_id=" . $calendars[$y]['calendarid'] . "";
				$dds->setSQL($sql);
				$dbEtags = $dds->GetDataset("labelled");
				for($j = 0; $j < count($etags); $j++) {
					//Check if ETAG has changed: Loop through all the ETAGs found in the
					//   database and look for a match.
					debug("PullCalendarUpdates:Matching ETAGs",basename(__FILE__));
					$ematch=false;
					$updating = false;
					for($k = 0; $k < count($dbEtags); $k++) {
						//debug ("comparing tag: " . $dbEtags[$k]['etag']);
						if ($etags[$j]['etag']==$dbEtags[$k]['etag']) $ematch=true;
					}
					if (!$ematch) {
						//Pull the event from the remote server for insert or update
						debug("PullCalendarUpdates:etag '" .$etags[$j]['etag'] . "' unmatched","0/".basename(__FILE__));
						debug("PullCalendarUpdates:Reading event href=" .$etags[$j]['uri'] ,basename(__FILE__));
						$event=CalDAV::GetCalObjectByID($etags[$j]['id']);
						debug("PullCalendarUpdates:Event data=\n" .$event . "\n",basename(__FILE__));
						$parsed=CalDAV::parseEvent($event);
						if ($parsed)  debug ("Event successfully parsed",basename(__FILE__));
						$sql="SELECT max(id), max(sequence), max(owner) from " . DB::$reminder_table . " WHERE uid='" . $parsed['UID'] . "'";
						$dds->setSQL($sql);
						$result_row=$dds->getNextRow();
						if (is_null($result_row[0])) {
							//INSERT  (new record)
							debug("PullCalendarUpdates: building SQL to insert new reminder \n" . $event . "\n","0/".basename(__FILE__));
							$S=CalDAV::getSQLBuilder($parsed,"INSERT",false);
							debug("PullCalendarUpdates: SQL builder instantiated",basename(__FILE__));
							$S->addColumn("owner",$owners[$x][0]);
							$S->addColumn("sequence",(string) rand(0,1000000000));
							//2020-09-09 moved this line to be specific for insert and not also for update
							$S->addColumn("calendar_id",$calendars[$y]['calendarid']);
						} else {
							//UPDATE
							$updating = true;
							debug("PullCalendarUpdates: building SQL to update reminder \n" . $event . "\n",basename(__FILE__));
							debug("PullCalendarUpdates: building SQL to update reminder '" . $parsed['SUMMARY'] . "'");
							$S=CalDAV::getSQLBuilder($parsed,"UPDATE",false);
							$S->addWhere("id=".$result_row[0]);
							//2020-09-09 added this line so that updates won't be applied to a task moved to a different cal.
							$S->addWhere("calendar_id=".$calendars[$y]['calendarid']);
						}
						$S->addColumn("etag",$etags[$j]['etag']);
						//$S->addColumn("url",$etags[$j]['href']);

						debug("PullCalendarUpdates: SQL built",basename(__FILE__));
						$sql=$S->getSQL();
						debug("PullCalendarUpdates: SQL:" . $sql,basename(__FILE__));
						$dds->setSQL($sql);
						debug("PullCalendarUpdates: Reminder added/updated",basename(__FILE__));
						if ($updating) {
							debug("Incoming update recognized.",basename(__FILE__));
							//if the incoming update has a new STATUS or COMPLETED line,
							//it gets special handling
							$incoming_completed=false;
							//Check COMPLETED for date
							if (isset($parsed['COMPLETED'])) {
								$incoming_completed=true;
								debug("Incoming update : COMPLETED is set to " . $parsed['COMPLETED'],basename(__FILE__));
							}
							//Check STATUS for COMPLETED
							if ($parsed['STATUS']=="COMPLETED") {
								$incoming_completed=true;
								debug("Incoming update : STATUS is set to COMPLETED",basename(__FILE__));
							}
							//We could look at the actual date value of "COMPLETED"
							// but that's a job for another day.
							// Since we pushed the sequence value out on local updates
							// we can read it back and see if it matches. We don't want to mark
							// an item complete more times than appropriate.
							if (!isset($parsed['X-CADENCE-SEQUENCE'])) {
								debug("Incoming event " . $parsed['SUMMARY'] . " has no sequence",basename(__FILE__));
								$parsed['X-CADENCE-SEQUENCE']="";
							}
							if ($result_row[1] != $parsed['X-CADENCE-SEQUENCE']) {
								$incoming_completed=false;
								debug("Incoming update (" . $parsed['X-CADENCE-SEQUENCE'] . ") has unmatched sequence (expected " . $result_row[1] . ")",basename(__FILE__));
							}
							if ($incoming_completed) {
								Reminders::MarkComplete($parsed['X-CADENCE-SEQUENCE'],true,$result_row[2]);
								debug("Incoming status update processed",basename(__FILE__));
							}
						}
					} //if (!$ematch)
				} // loop etags
				//debug("PullCalendarUpdates: Done matching ETAGs for calendar " . $calendars[$y]['name'],basename(__FILE__));

				debug("PullCalendarUpdates: Done processing calendar " . $calendars[$y]['uri'],basename(__FILE__));

			} //loop calendars
			debug("PullCalendarUpdates: Done processing calendars for owner ". $owners[$x][0],basename(__FILE__));
		} //loop principals
		debug("PullCalendarUpdates: Done processing.",basename(__FILE__));
	}

	public static function GetReminders ($CalendarID) {
		/**
		* @return array of events
		*/
		global $dds;
		$sql="SELECT id, etag, uri, calendardata from calendarobjects WHERE componenttype='VTODO' and calendarid=" . $CalendarID . "";
		$dds->setSQL($sql);
		$events = $dds->GetDataset("labelled");
		debug ("GetReminderETags: " . count($events) . "  tags found for calendar ID $CalendarID",basename(__FILE__));
		return $events;
	}

	public static function GetCalObjectByID ($ObjectID) {
		/**
		* @return iCal data
		*/
		global $dds;
		$sql="SELECT convert(calendardata using utf8) as cdata FROM calendarobjects WHERE id=" . $ObjectID;
		$dds->setSQL($sql);
		$row = $dds->getNextRow("labelled");
		return $row['cdata'];

	}


	public static function parseEvent($ICS) {
		//debug("parseEvent: \n" . $ICS,basename(__FILE__));
		/**
		* @return array of key-value pairs from ICS string
		*/
		$lines= explode("\n",$ICS);
		$return = array();
		$calendar=false;
		$uid = false;
		$summary=false;
		$arrlength = count($lines);
		for($x = 0; $x < $arrlength; $x++) {
			//debug("Processing line: " . $lines[$x]);

			//CR LF chars cause a couple of problems:
			$lines[$x] = str_replace(chr(10),"",$lines[$x]);
			$lines[$x] = str_replace(chr(13),"",$lines[$x]);

			$parts= explode(":",$lines[$x]);
			$partstring=$parts[0];
			if (count($parts)>1)  $partstring .= " [1] length (" . strlen($parts[1]) . ")= " . $parts[1];
			//debug(count($parts) . " line parts: [0] length (" . strlen($parts[0]) . ")= " . $partstring);
			//Assumption for now is that each input $ICS contains only one event or todo.
			//Some validation would be good. Require a UID, SUMMARY.
			if ($parts[0]=="BEGIN" and $parts[1]=="VCALENDAR")	{
				$calendar=true;
				//debug ("VCALENDAR recognized",basename(__FILE__));
			}
			if ($parts[0]=="BEGIN" and $parts[1]=="VEVENT")	$return["VCALENDAR"]="VEVENT";
			if ($parts[0]=="BEGIN" and $parts[1]=="VTODO") $return["VCALENDAR"]="VTODO";
			//After finding the VCALENDAR line, ignore any BEGIN and END lines
			if ($parts[0]!="BEGIN" and $parts[0]!="END") {
				if ($parts[0]=="UID" and count($parts)>1) $uid = true;
				if ($parts[0]=="SUMMARY" and count($parts)>1) $summary = true;
				//discard any (header) content before the BEGIN:VCALENDAR line
				if (count($parts)>1 and $calendar) {
					//debug ("Line $x content: " . $parts[0] . "=" . $parts[1],basename(__FILE__));
					$return[$parts[0]]=$parts[1];
				}

			}
		}
		//Ignore any HTTP resp codes, etc. at beginning
		$return['ics']=substr($ICS,strpos($ICS,"BEGIN:VCALENDAR"));

		if ($calendar and $uid and $summary) return $return; else {
			debug("parseEvent ERROR:",basename(__FILE__));
			if (!$uid) debug ("No UID",basename(__FILE__));
			if (!$summary) debug ("No SUMMARY",basename(__FILE__));
			if (!$calendar) debug ("Not a VCALENDAR",basename(__FILE__));
			debug ("full ICS: " . $ICS);
			return false;
		}
	}


	public static function getSQLBuilder ($parsed,$mode="INSERT",$current_user=true) {
		/**
		* @return SQLBuilder object from parsed ICS string
		*/
		debug ("CalDAV::getSQLBuilder : loading SQLbuilder class",basename(__FILE__));
		require_once("Hydrogen/clsSQLBuilder.php");
		debug ("CalDAV::getSQLBuilder : Checking UID",basename(__FILE__));
		//require UID to be set
		if (!isset($parsed['UID'])) {
			debug ("CalDAV::getSQLBuilder : no UID set");
			return false;
		}
		debug ("CalDAV::getSQLBuilder : creating instance",basename(__FILE__));
		$sqlb = new SQLBuilder($mode);
		debug ("CalDAV::getSQLBuilder : setting table name",basename(__FILE__));
		$sqlb->setTableName(DB::$reminder_table);
		if ($current_user) $sqlb->addColumn("owner",$_SESSION['username']);
		if ($current_user and ($mode=="UPDATE")) $sqlb->addWhere("owner='" .  $_SESSION['username']. "'");
		if ($mode=="UPDATE") $sqlb->addWhere("uid='" .  $parsed['UID']. "'");

		//The 'parsed' array also contains an 'ics' element with the unparsed data string.
		//We will be storing it separately as 'caldav_hidden' after stripping out
		//the elements we store as individual database fields.
		$caldav_hidden=$parsed['ics'];


		//Watch out for extra CR/LF characters, and remove a few lines
		if (!is_null($caldav_hidden)) {
			$temp=explode("\n",$caldav_hidden);
			for ($i=0; $i < count($temp); $i++) {
				//$temp[$i]=str_replace(chr(10),"",$temp[$i]); // this is the "\n" character, which we already exploded
				$temp[$i]=str_replace(chr(13),"",$temp[$i]);
				$temp[$i]=str_replace("BEGIN:VCALENDAR","--DELETE-ME--",$temp[$i]);
				$temp[$i]=str_replace("END:VCALENDAR","--DELETE-ME--",$temp[$i]);
				$temp[$i]=str_replace("BEGIN:VTODO","--DELETE-ME--",$temp[$i]);
				$temp[$i]=str_replace("END:VTODO","--DELETE-ME--",$temp[$i]);
				if(strpos($temp[$i],"VERSION:")===0) $temp[$i]="--DELETE-ME--";
				if(strpos($temp[$i],"CALSCALE:")===0) $temp[$i]="--DELETE-ME--";
				if(strpos($temp[$i],"DTSTAMP:")===0) $temp[$i]="--DELETE-ME--";
				if(strpos($temp[$i],"STATUS:")===0) $temp[$i]="--DELETE-ME--";
				//UID is supposed to be filtered out later as a mapped column
				//if(strpos($temp[$i],"UID:")!==false) $temp[$i]="--DELETE-ME--";
				if($temp[$i]=='') $temp[$i]="--DELETE-ME--";
			}
			$caldav_hidden=implode("\n",$temp);
			$caldav_hidden=str_replace('--DELETE-ME--' . "\n",'',$caldav_hidden);
			$caldav_hidden=rtrim($caldav_hidden,'--DELETE-ME--');
			$caldav_hidden=rtrim($caldav_hidden,"\n");
			$caldav_hidden=rtrim($caldav_hidden,'--DELETE-ME--');
		}

		/*
		These CalDAV fields are all mapped to database columns.
		The mapping is defined as an array in the DB class.
		//CREATED:20190617T203340Z
		//LAST-MODIFIED:20190617T203340Z
		//SUMMARY:New Task
		//DESCRIPTION:This is a test
		//UID:b27c8f51-1aa3-4ae7-b9c7-b1b725463bad
		//URL:www.monstro.us
		//LOCATION:here
		//PRIORITY:1
		$sqlb->addColumn("uid",$parsed['UID']);
		$sqlb->addColumn("summary",$parsed['SUMMARY']);
		$sqlb->addColumn("location",$parsed['LOCATION']);
		$sqlb->addColumn("url",$parsed['URL']);
		$sqlb->addColumn("description",$parsed['DESCRIPTION']);
		$sqlb->addColumn("priority",$parsed['PRIORITY']);
		$sqlb->addColumn("last_modified",$parsed['LAST-MODIFIED']);
		$sqlb->addColumn("created",$parsed['CREATED']);
		*/

		debug ("CalDAV::getSQLBuilder : reading mapped columns",basename(__FILE__));
		$fields= DB::calDAV_mapped_columns();

		debug ("CalDAV::getSQLBuilder : looping thru mapped columns",basename(__FILE__));
		//This line of code will help us find the last line in the string. Will remove the extra char later.
		$caldav_hidden .= "\n";
		$length = count($fields);
		for ($i = 0; $i < $length; $i++) {
			debug ("CalDAV::getSQLBuilder : checking column " . $fields[$i]['col_name'],basename(__FILE__));
			if (isset($parsed[$fields[$i]['calDAV_name']])) {
				debug ("CalDAV::getSQLBuilder : setting value " . $parsed[$fields[$i]['calDAV_name']],basename(__FILE__));
				//$sqlb->addColumn("uid",$parsed['UID']);
				$sqlb->addColumn($fields[$i]['col_name'],$parsed[$fields[$i]['calDAV_name']]);
				//remove this iCal line from 'caldav_hidden'
				$remove_me=$fields[$i]['calDAV_name'].":". $parsed[$fields[$i]['calDAV_name']] . "\n";
				$caldav_hidden=str_replace($remove_me,"",$caldav_hidden);

			}
		}
		$caldav_hidden=rtrim($caldav_hidden,"\n");
		debug ("CalDAV::getSQLBuilder : hidden CalDAV data: " . $caldav_hidden,basename(__FILE__));
		$sqlb->addColumn("caldav_hidden",$caldav_hidden);

		return $sqlb;

	}
}

?>
