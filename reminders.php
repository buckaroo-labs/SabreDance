
<?php 
include "Hydrogen/pgTemplate.php";
require_once 'common.php';
require_once 'clsDB.php';
$_SESSION['referring_page']=$_SERVER['REQUEST_URI'];
require_login();

$pagetitle="Reminders | Cadence";
$headline = '<h1>Cadence</h1>' ;
require_once 'Hydrogen/libDebug.php';
require_once 'clsReminder.php';


?>


<script>
$(document).ready(function(){
  $(".mark_reminder_complete").html('<img src="images/checkbox.png" height="16">');
  $(".edit_reminder").html('<img src="images/edit.png" height="16">');
  $(".delete_reminder").html('<img src="images/trash.png" height="16">');
});


</script>



<!-- Main content: shift it to the right by 250 pixels when the sidebar is visible -->
<div class="w3-main w3-container w3-padding-16" style="margin-left:250px">

<?php 
include 'Hydrogen/elemLogoHeadline.php';  

function show_upcoming () {
		global $dds;
		global $address_classes;
		global $linkURLs;
		global $linkTargets;
		global $keycols;
		global $invisible;
			
		/*
		
		SQL FOR UPCOMING REMINDERS
		
		*/
		$sql = "select id as '(edit)', summary as 'Title', date_format(start_date,'%M %D') as 'Start', date_format(due_date,'%M %D') as 'Due'";
		$sql = $sql . ",calendar_id	as 'Calendar' , id as '(delete)' from " . DB::$reminder_table . " where owner='" . $_SESSION['username'] . "' ";
		$sql = $sql . " and ifnull(start_date,now()- interval 1 day) BETWEEN current_timestamp() and date_add(current_timestamp(), interval 90 day)  ";
		//$sql = $sql . " and ifnull(snooze_date,now()- interval 1 day) > current_timestamp()";
		
		$sql = $sql . " and ifnull(end_date,now()+ interval 1 day) > now() ";

		$sql = $sql . " ORDER BY start_date";
		
		//echo "<P>SQL:" . $sql . "</P>";

		$result = $dds->setMaxRecs(50);
		$result = $dds->setSQL($sql);
		$page_count = $dds->getPageCount();
		if ($page_count>0) {
			unset($address_classes);
			unset($linkURLs);
			unset($linkTargets);
			unset($keycols);
			unset($invisible);
			unset($hide_headers);
			$linkURLs[0] = 'edit_reminder.php?ID=';
			$linkURLs[1] = 'view_reminder.php?ID=';
			$keycols=array();
			$invisible=array();
			$link_targets=array();
			$hide_headers[0] = 1;
			$keycols[1] = 0;
			$address_classes[0]='edit_reminder';	
			$linkURLs[5] = 'reminders.php?delete=';
			$address_classes[5]='delete_reminder';			
			echo "<H3>Upcoming</h3>";
			$table=new HTMLTable($dds->getFieldNames(),$dds->getFieldTypes());
			$table->defineRows($linkURLs,$keycols,$invisible,$address_classes,$link_targets,$hide_headers);
			$table->start();
			while ($result_row = $dds->getNextRow()){
				$table->addRow($result_row);
			}
			$table->finish();
		}
	
}	
	 

if (isset($_SESSION['username'])) {
	require_once 'Hydrogen/clsDataSource.php';
	require_once 'Hydrogen/clsHTMLTable.php';
	require_once 'Hydrogen/libFilter.php';

	if (isset($_GET['mark_complete'])) {
			Reminders::MarkComplete((int)$_GET['mark_complete'],true);
			
	}
	if (isset($_GET['delete'])) {
		Reminders::Delete((int)$_GET['delete'],true);
		
	}
	
	$timeofday = date("Hi");
	$dayofyear = date("z");
	$dayofweek = date("l");
	//Day name comparisons will be made on first character. 
	//Distinguish e.g. T(hursday) from T(uesday) by changing case
	if ($dayofweek=="Thursday") $dayofweek = strtolower($dayofweek); 
	if ($dayofweek=="Sunday") $dayofweek = strtolower($dayofweek);
	
	/*
		
		SQL FOR CURRENT REMINDERS
		
	*/
	
	$sql = "SELECT sequence as '(check)', id as '(edit)', summary as 'Title', date_format(start_date,'%M %D') as 'Start', date_format(due_date,'%M %D') as 'Due'";
	$sql .= ", CASE WHEN due_date < NOW() THEN 1 ELSE 0 END as overdue ";
	$sql = $sql . ",calendar_id as 'Calendar', id as '(delete)'	from " . DB::$reminder_table . " where owner='" . $_SESSION['username'] . "' ";
	$sql = $sql . " and ifnull(start_date,now()- interval 1 day) < current_timestamp() ";
	$sql = $sql . " and ifnull(snooze_date,now()- interval 1 day) < current_timestamp() ";
	
	$sql = $sql . " AND CASE WHEN ifnull(day_start,0) < ifnull(day_end,2359) THEN " . $timeofday . "  BETWEEN ifnull(day_start,0) and ifnull(day_end,2359) ELSE " . $timeofday . "  NOT BETWEEN ifnull(day_end,2359) and ifnull(day_start,0) END ";

	$sql = $sql . " AND CASE WHEN ifnull(season_start,0) < ifnull(season_end,364) THEN " . $dayofyear . " BETWEEN ifnull(season_start,0) and ifnull(season_end,364) ELSE " . $dayofyear .  " NOT BETWEEN ifnull(season_end,364) and ifnull(season_start,0) END ";

	$sql = $sql . " and ifnull(days_of_week,'MTWtFSs') like '%" . substr($dayofweek,0,1) . "%'";
	
	$sql = $sql . " and ifnull(end_date,now()+ interval 1 day) > now()";
	$sql = $sql . " ORDER BY ifnull(due_date,now()+ interval 99 year), start_date";
	//echo "<P>SQL:" . $sql . "</P>";

	$result = $dds->setMaxRecs(50);
	$result = $dds->setSQL($sql);
	$page_count = $dds->getPageCount();
	if ($page_count>0) {
		unset($address_classes);
		unset($linkURLs);
		unset($linkTargets);
		unset($keycols);
		unset($invisible);
		unset($hide_headers);
		$linkTargets=null;
		$keycols=null;
		$invisible=null;
		$invisible[5] = 1;
		$linkURLs[0] = 'reminders.php?mark_complete=';
		$address_classes[0]='mark_reminder_complete';
		$linkURLs[1] = 'edit_reminder.php?ID=';
		$address_classes[1]='edit_reminder';
		$linkURLs[2] = 'view_reminder.php?ID=';
		$keycols[2] = 1;
		$linkURLs[7] = 'reminders.php?delete=';
		$address_classes[7]='delete_reminder';		
		$hide_headers[0]=1;
		$hide_headers[1]=1;
		$hide_headers[5]=1;		
		//$address_classes[2]='view_reminder';
		echo "<H3>Current</h3>";
		$table=new HTMLTable($dds->getFieldNames(),$dds->getFieldTypes());
		$table->defineRows($linkURLs,$keycols,$invisible,$address_classes,$linkTargets,$hide_headers);
		$table->start();
		while ($result_row = $dds->getNextRow()){
			$style='background-color: #a4f995; color: black';
			if (strlen($result_row[4]) > 1 ) $style='background-color: #f5f9b1; color: black';
			if ($result_row[5] == 1 ) $style='background-color: #f44d1f; color: white';
			$table->addRow($result_row,$style); 
		}
		$table->finish();
		
		show_upcoming();
	} else {
		echo '<h3>No current reminders.</h3><p> Are you all caught up? Excellent!</p>';	
		show_upcoming();
	}
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
