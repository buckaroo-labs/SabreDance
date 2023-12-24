
<?php 
$pagetitle="Reminder | Cadence";
$headline = '<h1>Cadence</h1>' ;
include "Hydrogen/pgTemplate.php";
require_once 'Hydrogen/libDebug.php';
require_once 'common.php';
require_once 'clsDB.php';
$this_page="view_reminder.php"
?>

<!-- Main content: shift it to the right by 250 pixels when the sidebar is visible -->
<div class="w3-main w3-container w3-padding-16" style="margin-left:250px">

<?php 

//unset these to remove the elements from the page, but include elemLogoHeadline to push the main section below the nav bar. Find a cleaner way of doing this later.
unset ($logo_image);
unset ($headline);
include 'Hydrogen/elemLogoHeadline.php';  

if (isset($_SESSION['username'])) {
	require_once 'Hydrogen/clsDataSource.php';
	require_once 'Hydrogen/clsHTMLTable.php';
	require_once 'Hydrogen/libFilter.php';
	require_once 'Hydrogen/clsSQLBuilder.php';
	
	if (isset($_POST['ID'])) include "post_reminder.php";
	
	//display the reminder as read-only
	if (isset($_GET['ID'])) $reminderID = (int)$_GET['ID'];

	if ($reminderID=="new") {
		$where="sequence=" . $timestamp;
	} else {
		$where="id=" . $reminderID;
	}

	$result = $dds->setSQL("SELECT * FROM " . DB::$reminder_table . " WHERE  ". $where . " AND owner ='" . $_SESSION['username'] . "'");
	$remdata = $dds->getNextRow("labeled");

	$startdatestr = date("Y-m-d",strtotime($remdata['start_date']));
	$starttimestr = date("H:i",strtotime($remdata['start_date']));

	$implementation_note=false;
	$output = "<table><tr><td>Title: </td><td>" . $remdata['summary'] . "</td><tr>";
	$output .= "<tr><td>Start: </td><td>$startdatestr at $starttimestr</td><tr>";

	if (isset($remdata['description'])) {
		if (!is_null($remdata['description'])) {
			$output .= "<tr><td>Notes: </td><td>" . $remdata['description'] ."</td><tr>";	
		}
	}

	if (isset($remdata['calendar_id'])) {
		$calendar_name='Default';
		if (!is_null($remdata['calendar_id'])) {
			//look up the name
			$sql = "SELECT c.name,a.alias FROM " . DB::$caldav_cal_table ;
			$sql .= " c inner join " . DB::$caldav_acct_table . " a on a.id=c.remote_acct_id ";
			$sql .= " where c.id='" . $remdata['calendar_id'] . "' ";
			$result=$dds->setSQL($sql);
			$result_row=$dds->getNextRow();
			$calendar_name=$result_row[0] . '('. $result_row[1] . ')';
		}
		$output .= "<tr><td>Calendar: </td><td>" . $calendar_name ."</td><tr>";	
	}
	
	if (isset($remdata['location'])) {
		if (!is_null($remdata['location'])) {
			$output .= "<tr><td>Location: </td><td>" . $remdata['location'] ."</td><tr>";	
		}
	}
	
	if (isset($remdata['category'])) {
		if (!is_null($remdata['category'])) {
			$output .= "<tr><td>Tags: </td><td>" . $remdata['category'] ."</td><tr>";	
		}
	}
	
	if (isset($remdata['priority'])) {
		if (!is_null($remdata['priority'])) {
			$output .= "<tr><td>Priority: </td><td>" . $remdata['priority'] ."</td><tr>";	
		}
	}


	if (isset($remdata['recur_units'])) {
		if (!is_null($remdata['recur_units'])) {
			if ($remdata['recur_float']==1) $recur_float = "completion"; else $recur_float="start";
			$temp = decode_scale_and_units($remdata['recur_scale'],$remdata['recur_units']);
			$temp = "Every $temp after previous $recur_float";
			
			if (isset($remdata['end_date'])) {
				if (!is_null($remdata['end_date'])) {

				$enddatestr = date("Y-m-d",strtotime($remdata['end_date']));
				$endtimestr = date("H:i",strtotime($remdata['end_date']));
				//$output .= " and will not recur after " . $enddatestr  . " at " . $endtimestr;
				$temp .= " until $enddatestr at $endtimestr";
				}
			}			
					
			
			$output .= "<tr><td>Recurrence: </td><td>$temp</td><tr>";
		}
	}
	
	
	
	if (isset($remdata['grace_units'])) {

		$duedatestr = date("Y-m-d",strtotime($remdata['due_date']));
		$duetimestr = date("H:i",strtotime($remdata['due_date']));		
		if (!is_null($remdata['grace_units'])) {
			$temp = decode_scale_and_units($remdata['grace_scale'],$remdata['grace_units'],true);
			$output .= "<tr><td>Due: </td><td>$duedatestr at $duetimestr ($temp after start)</td><tr>";
		}
	}
		
	if (isset($remdata['passive_units'])) {
		if (!is_null($remdata['passive_units'])) {
			$alarmdatestr = date("Y-m-d",strtotime($remdata['active_date']));
			$alarmtimestr = date("H:i",strtotime($remdata['active_date']));	
			$temp = decode_scale_and_units($remdata['passive_scale'],$remdata['passive_units'],true);
			$alarm = decode_scale_and_units($remdata['alarm_interval_scale'],$remdata['alarm_interval_units']);
			$output .= "<tr><td>Alarms*: </td><td>Every $alarm beginning $alarmdatestr at $alarmtimestr ($temp after start)</td><tr>";
			$implementation_note = true;
		}
	}
	
	if (isset($remdata['url'])) {
		if (!is_null($remdata['url'])) {
			$output .= '<tr><td>URL: </td><td><a href="'. $remdata['url'] .'">' . $remdata['url'] ."</a></td><tr>";	
		}
	}


	//$snooze_units = $remdata['snooze_units'];
	//$snooze_scale = $remdata['snooze_scale'];
	
	$days_of_week= "_" . $remdata['days_of_week'];
	if(is_null($remdata['days_of_week'])) $days_of_week="_MTWtFSs";
	if (!is_null($remdata['season_start'])) {
		$blackout_days=true;
		$season_start= (int) $remdata['season_start'] + 1;
	} else {
		$season_start= "1";
	}
	if (!is_null($remdata['season_end'])) {
		$blackout_days=true;
		$season_end= (int) $remdata['season_end'] +1;
	} else {
		$season_end= "365";
	}
	if (!is_null($remdata['day_start'])) {
		$blackout_hours=true;
		$temp = (string) $remdata['day_start'];
		$tempmin = substr($temp,1,-2);
		$temphour = str_replace($tempmin,'',$temp);
		$tempminute = (int) $tempmin;
		if ($tempminute > 59) $tempminute=59;
		if ($tempminute < 10) $tempminute= "0" . $tempminute;
		if ($temphour < 10) $temphour= "0" . $temphour;
		$tod_start= $temphour . ":" . $tempmin ;
	} else {
		$tod_start= "00:00";
	}
	if (!is_null($remdata['day_end'])) {
		$blackout_hours=true;
		$temp = (string) $remdata['day_end'];
		$tempmin = substr($temp,1,-2);
		$temphour = str_replace($tempmin,'',$temp);
		$tempminute = (int) $tempmin;
		if ($tempminute > 59) $tempminute=59;
		if ($tempminute < 10) $tempminute= "0" . $tempminute;
		if ($temphour < 10) $temphour= "0" . $temphour;
		$tod_end= $temphour . ":" . $tempmin ;
	} else {
		$tod_end= "23:59";
	}
	$output .="</table>";
	if ($implementation_note) $output .= "<P>(* = not implemented)</P>" ;
	echo '<p name="reminder_description">' . $output . "</p>" . '<p><a href="edit_reminder.php?ID=' . $remdata['id'] . '">Edit</a></p>';

	
} else {
	echo '<P>Not logged in.</p>';	
}	
?>
<!-- END MAIN -->
<p></p>
<p></p>
</div>

<?php include "Hydrogen/elemNavbar.php"; ?>
<?php include "Hydrogen/elemFooter.php"; ?>
</body></html>
