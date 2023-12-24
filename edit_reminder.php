
<?php 
$pagetitle="Reminders | Cadence";
$headline = '<h1>Cadence</h1>' ;
include "Hydrogen/pgTemplate.php";
require_once 'Hydrogen/libDebug.php';
require_once 'clsDB.php';

?>

<!-- Main content: shift it to the right by 250 pixels when the sidebar is visible -->
<div class="w3-main w3-container w3-padding-16" style="margin-left:250px">

<?php 

//unset these to remove the elements from the page, but include elemLogoHeadline to 
//  push the main section below the nav bar. Find a cleaner way of doing this later.
unset ($logo_image);
unset ($headline);
include 'Hydrogen/elemLogoHeadline.php'; 

$rem_id=0;
$new=false;
if ($_GET['ID']=="new") {
	$new=true; 
	$rem_id="new";
} else { 
	$rem_id = (int) $_GET['ID'];
	debug("Editing reminder ID " . $rem_id);
}

if (isset($_SESSION['username'])) {
	require_once 'Hydrogen/clsDataSource.php';
	require_once 'Hydrogen/clsHTMLTable.php';
	require_once 'Hydrogen/libFilter.php';
	
	$timeofday = date("Hi");
	$dayofyear = date("z");
	$dayofweek = date("l");
	//Day name comparisons will be made on first character. 
	//Distinguish e.g. T(hursday) from T(uesday) by changing case
	if ($dayofweek=="Thursday") $dayofweek = strtolower($dayofweek); 
	if ($dayofweek=="Sunday") $dayofweek = strtolower($dayofweek);
	
	 
	//STEP ONE
	//Collect category, title, description, start, end ,notes, priority
	//"done" or "more"

	?>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
	
	
	<?php 
		if ($new) {
			echo ('<h2>New reminder</h2>'); 
			$startdatestr = date("Y-m-d");
			$starttimestr = date("H:i");
			$priority =3;
			$recur_float = 1;
			$recur_units = 1;
			$recur_scale = 1;
			$grace_units = 1;
			$grace_scale = 1;
			$passive_units = 1;
			$passive_scale = 1;
			$alarm_interval_units = 1;
			$alarm_interval_scale = 1;
			$days_of_week="_MTWtFSs";
			$season_start="1";
			$season_end="365";
			$tod_start="00:00";
			$tod_end="23:59";
			$calendar_id=0;
		} else {
			echo ('<h2>Edit reminder</h2>');
			$result = $dds->setSQL("SELECT * FROM " . DB::$reminder_table . " WHERE id=" . $rem_id . " AND owner ='" . $_SESSION['username'] . "'");
			$remdata = $dds->getNextRow("labeled");
			$startdatestr = date("Y-m-d",strtotime($remdata['start_date']));
			$starttimestr = date("H:i",strtotime($remdata['start_date']));
			if (isset($remdata['end_date'])) {
				if (!is_null($remdata['end_date'])) {
				$enddatestr = date("Y-m-d",strtotime($remdata['end_date']));
				$endtimestr = date("H:i",strtotime($remdata['end_date']));
				}
			}
			$priority = $remdata['priority'];
			$calendar_id = $remdata['calendar_id'];
			$recur_float = $remdata['recur_float'];
			$recur_units = $remdata['recur_units'];
			$recur_scale = $remdata['recur_scale'];
			$grace_units = $remdata['grace_units'];
			$grace_scale = $remdata['grace_scale'];
			$passive_units = $remdata['passive_units'];
			$passive_scale = $remdata['passive_scale'];
			$alarm_interval_units = $remdata['alarm_interval_units'];
			$alarm_interval_scale = $remdata['alarm_interval_scale'];
			$snooze_units = $remdata['snooze_units'];
			$snooze_scale = $remdata['snooze_scale'];
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
		}
	?>
	
	<form class="w3-container" action="view_reminder.php" method="post">
		<input name="ID" type="hidden" value="<?php echo($rem_id); ?>">
		<input name="DIRTY" type="hidden" value="Y">
		<?php 
		if (!is_null($calendar_id)) echo'<input name="OLD_CALENDAR_ID" type="hidden" value="' . $calendar_id .'">';
		?>
		<p>
			<label class="w3-text-red">Title (required)</label>
			<!-- The UI max length should be several chars smaller than the database
			to allow for HTML encoding e.g. "'" to "&rsquo;" -->
			<input name="summary" class="w3-input w3-border" type="text" maxlength="30" <?php if(!$new) echo ' value="' . $remdata['summary'] . '"'; ?> required>
		</p>
		 
		<div class="w3-container w3-cell-row">

			<div id="StepOne"  class="w3-container w3-mobile w3-cell">
				<p>
					 <label>Calendar</label>
					 <select name="CALENDAR_ID" class="w3-input w3-border" >
					 	<option value="0">Default</option>
					 	<?php
						 		$sql = "SELECT c.id,c.name,a.alias FROM " . DB::$caldav_cal_table ;
								 $sql .= " c inner join " . DB::$caldav_acct_table . " a on a.id=c.remote_acct_id ";
								 $sql .= " where c.owner='" . $_SESSION['username'] . "' ";
								 $sql .= " ORDER BY c.name";
								 $result = $dds->setSQL($sql);
								 while ($result_row = $dds->getNextRow()){
									 $selectLabel=$result_row[1] .' ('. $result_row[2] . ')';
									 if ($result_row[0]==$calendar_id ) {
										 echo '<option value="' . $result_row[0] . '"  selected>' . $selectLabel . '</option>';
									} else {
										 echo '<option value="' . $result_row[0] . '">' . $selectLabel . '</option>';
									 }

								}

						?>
					 </select>
				</p>
				<p>
					 <label>Location</label>
					 <input name="location" class="w3-input w3-border" type="text" maxlength="255" <?php if(!$new) echo ' value="' . $remdata['location'] . '"'; ?> >
				</p>
				<p>
					<label>Tags (comma-separated)</label>
					<input name="category" class="w3-input w3-border" type="text" maxlength="30"  <?php if(!$new) echo ' value="' . $remdata['category'] . '"'; ?> ></p>
					<div id="StartDateAndTime" class="w3-container w3-card-4 w3-amber">
						<p>
							<label>Start</label>
							<input name="StartDate" class="w3-input w3-border" type="date" value="<?php echo $startdatestr; ?>" required>
							<input name="StartTime" class="w3-input w3-border" type="time" value="<?php echo $starttimestr; ?>" required>
						</p>
					</div>

				</p> 
				<p>
					<label class="w3-text-red">Priority</label>
					<select name="priority" class=" w3-border" value="3" required>
						<option value="1" <?php if ($priority==1)echo ' selected'; ?> >1 (highest)</option>
						<option value="2" <?php if ($priority==2)echo ' selected'; ?> >2</option>
						<option value="3" <?php if ($priority==3)echo ' selected'; ?> >3</option>
						<option value="4" <?php if ($priority==4)echo ' selected'; ?> >4</option>
						<option value="5" <?php if ($priority==5)echo ' selected'; ?> >5 (medium)</option>
						<option value="6" <?php if ($priority==6)echo ' selected'; ?> >6</option>
						<option value="7" <?php if ($priority==7)echo ' selected'; ?> >7</option>
						<option value="8" <?php if ($priority==8)echo ' selected'; ?> >8</option>
						<option value="9" <?php if ($priority==9)echo ' selected'; ?> >9 (lowest)</option>						
					</select>
				</p>
				<p>
					 <label>URL</label>
					 <input name="url" class="w3-input w3-border" type="text" maxlength="555" <?php if(!$new) echo ' value="' . $remdata['url'] . '"'; ?> >
				</p>
				<p>
					<label>Notes</label><br>
					<!-- <input name="note" class="w3-input w3-border" type="text"  > -->
					<!-- thanks to https://www.studentstutorial.com/html/responsive-textarea -->
					<div style="max-width: 750px; margin: 0px auto; margin-top: 20px;"><div style="width: 100%; height: auto; float:left" >
					<textarea style="float: left; width: 100%;min-height: 75px;outline: none; resize: none;border: 1px solid grey;" name="description" rows="8" cols="40" wrap="soft"> <?php if(!$new) echo  str_replace('\n',"\n",$remdata['description']) ; ?> </textarea>
					</div></div>
				 </p>
				 <br> 
				  
			</div>
		    <p></p>
			<div id="StepTwo"  class="w3-container w3-mobile w3-cell ">
				<div id="Recurrence" class="w3-container w3-card-4 w3-pale-green">
					<h4>Recurrence</h4>
					<p> 
						<input name="Recurrence" value="RecurYN" type="checkbox" <?php if(!$new AND $recur_units!="") echo ' checked';  ?> > This item will recur every<br>
						<input name="recur_units" class=" w3-border" type="number"  <?php if($recur_units!="") echo ('value="'. $recur_units . '"'); else echo('value="1"');  ?> required>
						<select name="recur_scale" class=" w3-border" value="<?php echo $recur_scale; ?>" required>
							<option value="0" <?php if ($recur_scale==0)echo ' selected'; ?> >hours</option>
							<option value="1" <?php if ($recur_scale==1 OR $recur_scale=="")echo ' selected'; ?> >days</option>
							<option value="2" <?php if ($recur_scale==2)echo ' selected'; ?> >weeks</option>
							<option value="3" <?php if ($recur_scale==3)echo ' selected'; ?> >months</option>
							<option value="4" <?php if ($recur_scale==4)echo ' selected'; ?> >years</option>
						</select> <br>after: <br>
						<input type="radio" name="recur_float" value="0" <?php if ($recur_float==0) echo ' checked'; ?> > its previous start time<br>
						<input type="radio" name="recur_float" value="1" <?php if ($recur_float==1) echo ' checked'; ?> > its previous completion
					</p>
					<div id="EndDateAndTime" >
						<p>
							<label>Until: </label>
							<input name="EndDate" class="w3-input w3-border" type="date"  <?php if(!$new and isset($enddatestr) ) echo ' value="' . $enddatestr . '"'; ?>>
							<input name="EndTime" class="w3-input w3-border" type="time"  <?php if(!$new and isset($endtimestr)) echo ' value="' . $endtimestr . '"'; ?>>
						</p>
						<br>
					</div>
				</div>
				<br>
				<div id="GraceTime" class="w3-container w3-card-4 w3-pale-yellow">
					<h4>Grace time</h4>
					<p>
						<input name="GraceTime" value="DueYN" type="checkbox" <?php if(!$new AND $grace_units!="") echo ' checked';  ?>> This item will be due<br>
						<input name="grace_units" class="w3-border" type="number"  <?php if($grace_units!="") echo ('value="'. $grace_units . '"'); else echo('value="1"');  ?> required>
						<select name="grace_scale" class=" w3-border" value="<?php echo $grace_scale; ?>" required>
							<option value="0" <?php if ($grace_scale==0)echo ' selected'; ?> >hours</option>
							<option value="1" <?php if ($grace_scale==1 OR $grace_scale=="")echo ' selected'; ?> >days</option>
							<option value="2" <?php if ($grace_scale==2)echo ' selected'; ?> >weeks</option>
							<option value="3" <?php if ($grace_scale==3)echo ' selected'; ?> >months</option>
							<option value="4" <?php if ($grace_scale==4)echo ' selected'; ?> >years</option>
						</select> <br> after it starts
					</p>
				</div>
				<br>
				<div id="Alarms" class="w3-container w3-card-4 w3-pale-red">
					<h4>Alarms</h4>
					<p>
						<input name="Alarms" value="AlarmYN" type="checkbox" <?php if(!$new AND $passive_units!="") echo ' checked';  ?> > Raise an alarm every<br>
						<input name="alarm_interval_units" class=" w3-border" type="number" <?php if($alarm_interval_units!="") echo ('value="'. $alarm_interval_units . '"'); else echo('value="1"');  ?>  required>
						<select name="alarm_interval_scale" class=" w3-border" value="<?php echo $alarm_interval_scale; ?>" required>
							<option value="0" <?php if ($alarm_interval_scale==0)echo ' selected'; ?> >hours</option>
							<option value="1" <?php if ($alarm_interval_scale==1 OR $alarm_interval_scale=="")echo ' selected'; ?> >days</option>
							<option value="2" <?php if ($alarm_interval_scale==2)echo ' selected'; ?> >weeks</option>
							<option value="3" <?php if ($alarm_interval_scale==3)echo ' selected'; ?> >months</option>
							<option value="4" <?php if ($alarm_interval_scale==4)echo ' selected'; ?> >years</option>
						</select>
						<br>if this item is not completed <br>
						<input name="passive_units" class=" w3-border" type="number" <?php if($passive_units!="") echo ('value="'. $passive_units . '"'); else echo('value="1"');  ?>  required>
						<select name="passive_scale" class=" w3-border" value="<?php echo $passive_scale; ?>" required>
							<option value="0" <?php if ($passive_scale==0)echo ' selected'; ?> >hours</option>
							<option value="1" <?php if ($passive_scale==1 OR $passive_scale=="")echo ' selected'; ?> >days</option>
							<option value="2" <?php if ($passive_scale==2)echo ' selected'; ?> >weeks</option>
							<option value="3" <?php if ($passive_scale==3)echo ' selected'; ?> >months</option>
							<option value="4" <?php if ($passive_scale==4)echo ' selected'; ?> >years</option>
						</select>  <br>after it starts
					</p>
				</div>
		</div>
		  <br>
			<div id="StepThree" class="w3-container w3-mobile w3-cell w3-hide-large w3-hide-small w3-hide-medium">
				<div id="DaysOfWeek" class="w3-container w3-card-4 w3-light-blue">
					<h4>Days of week</h4>
					<p>
						Show me this item on these days of the week:<br>
						<input name="MondayYN" value="1" type="checkbox" <?php if (strpos($days_of_week,"M")) echo ' checked'; ?> >Mon 
						<input name="TuesdayYN" value="1" type="checkbox" <?php if (strpos($days_of_week,"T")) echo ' checked'; ?> >Tue
						<input name="WednesdayYN" value="1" type="checkbox" <?php if (strpos($days_of_week,"W")) echo ' checked'; ?> >Wed 
						<input name="ThursdayYN" value="1" type="checkbox" <?php if (strpos($days_of_week,"t")) echo ' checked'; ?> >Thu 
						<input name="FridayYN" value="1" type="checkbox" <?php if (strpos($days_of_week,"F")) echo ' checked'; ?> >Fri 
						<input name="SaturdayYN" value="1" type="checkbox" <?php if (strpos($days_of_week,"S")) echo ' checked'; ?> >Sat 
						<input name="SundayYN" value="1" type="checkbox" <?php if (strpos($days_of_week,"s")) echo ' checked'; ?> >Sun 
					</p>
				</div>
				<br>
				<div id="TimeOfDay" class="w3-container w3-card-4 w3-blue-gray">
					<h4>Time of Day</h4>
					<p>
						<input name="SilentHoursYN" value="Y" type="checkbox" <?php if(!$new AND (isset($blackout_hours) )) echo ' checked';  ?>> This item will be visible only from <br>
						<input name="TimeOfDayStart" class=" w3-border" type="time" value="<?php echo $tod_start; ?>" required>
						to 
						<input name="TimeOfDayEnd" class=" w3-border" type="time" value="<?php echo $tod_end; ?>" required>
					</p>
				</div>
				<br>
				<div id="TimeofYear" class="w3-container w3-card-4 w3-dark-gray">
					<h4>Time of Year</h4>
					<p>
						<input name="SilentDaysYN" value="Y" type="checkbox" <?php if(!$new AND (
																								isset($blackout_days) 
																								)
																					) echo ' checked';  ?>> This item will be visible only from days <br>
						<input name="season_start" class=" w3-border" type="number" value="<?php echo $season_start; ?>" required>
						to 
						<input name="season_end" class=" w3-border" type="number" value="<?php echo $season_end; ?>" required> 
					</p> 
				</div>
				<br>

			</div>
		</div>
		<input name="btnSubmit" type="Submit" value="Done">

	</form>
<?php
} else {
	echo '<p>Not logged in.</p>';	
}	
?>

<!-- END MAIN -->
<p></p>
<p></p>
</div>
<?php include "Hydrogen/elemNavbar.php"; ?>
<?php include "Hydrogen/elemFooter.php"; ?>
</body></html>
