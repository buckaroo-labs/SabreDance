
<?php 
$pagetitle="CalDAV | Cadence";
$headline = '<h1>Cadence</h1>' ;
include "Hydrogen/pgTemplate.php";
require_once 'Hydrogen/libDebug.php';
require_once 'Hydrogen/clsSQLBuilder.php';
require_once 'Hydrogen/clsDatasource.php';
require_once 'clsDB.php';
require_once 'clsCalDAV.php';
require_once 'caldav-client.php';

/*

This page has five use cases:
1. GET (no "ID" set; or invalid ID for user) : Just show all existing accounts with test and edit buttons and a blank form to add another.
		(incomplete:) Show CalDAV calendars.
2. GET (?action=delete&ID= ): remove the account (and any associated calendars) if the ID belongs to the user and 
		then show existing accounts and a blank form.
3. GET (?ID=):  Edit an existing account (if the user owns it); prepopulate the form. If the user doesn't own the ID, ignore it.
4. $_POST['DIRTY_ACCOUNT'] : Save a new or dirty record to the DB before showing existing accounts. 
		Show the account in RED if connect failed, GREEN if OK. 
		Show a blank form if record was updated; show a list of calendars for the new account if one was added.
5. $_POST['SELECT_CALENDARS'] : Save the calendar selections for the previous use case, load the calendar data, and 
		then show a blank entry form.

*/

//These are being initialized. Their values may change as GET and POST variables are assessed.
$new=true;
$caldav_id="new";
$bad_connection=false;
$no_calendars=false;
$inserted="_none_";
$message="";

if (isset($_POST['SELECT_CALENDARS'])) {
	//Case 5
	$arrlength = count($_POST);
	for($x = 0; $x < $arrlength; $x++) {
		if (isset($_POST['cal_checkbox'. $x])) {
			//The value for this key will look like "/calendarname/a5d39a41-1898-ff3c-db9f-111d854564db/",
			$parts = explode("/",$_POST['cal_checkbox'. $x]);
			$cal_uid = $parts[2];
			$cal_name = $parts[1];

			//Select the ID from most recent account record having the account alias that was just inserted
			// and insert that into the calendar table
			$sql = "INSERT INTO " . DB::$caldav_cal_table . "(owner, name, remote_acct_id, uid) ";
			$sql .= " SELECT '" . $_SESSION['username'] . "','" . $cal_name . "', max(id), '" . $cal_uid . "'";
			$sql .= " FROM " . DB::$caldav_acct_table;
			$sql .= " WHERE alias='" . $_POST['SELECT_CALENDARS'] . "' AND owner='" . $_SESSION['username'] . "' GROUP BY alias";

			$dds->setSQL($sql);
			
			
			//Load the reminder data from the calendar:
			//	Get the account info from the DB first
			$sql = "SELECT alias, rhost, rport, ruser, rpassword FROM " . DB::$caldav_acct_table . " WHERE id=";
			$sql .= "(SELECT remote_acct_id FROM " . DB::$caldav_cal_table . " WHERE uid = '" . $cal_uid . "')";
			$dds->setSQL($sql);
			$result_row = $dds->GetNextRow("labelled");
						
			$account = array(
			"user"=>$result_row['ruser'],
			"pass"=>$result_row['rpassword'],
			//"uri"=>"http://localhost:5232/test/",
			"uri"=>"http://" . $result_row['rhost'] . ":" . $result_row['rport']. "/" . $result_row['ruser'] . "/",
			);

			$cal = new CalDAVClient( $account["uri"], $account["user"], $account["pass"], "dummy" );
			CalDAV::LoadCalendarReminders($cal,$cal_uid);
			
		}
	}
}

if (isset($_POST['DIRTY_ACCOUNT'])) {
	//Case 4
	if ($_POST['ID']=="new") {
		$sqlb = new SQLBuilder("INSERT");
		$inserted=$_POST['alias'];
	} else {
		$sqlb = new SQLBuilder("UPDATE");
		$sqlb->addWhere("id='" .  (int) $_POST['ID']. "'");
		$sqlb->addWhere("owner='" .  $_SESSION['username']. "'");
	}
	$sqlb->setTableName(DB::$caldav_acct_table);
	$sqlb->addColumn("owner",$_SESSION['username']);
	//process POST variables into sanitized SQL insert
	$columns = array('alias',
					'ruser',
					'rpassword',
					'rhost',
					'rport'					
					);
	$sqlb->addVarColumns($columns);
	$SQL=$sqlb->getSQL();
	$dds->setSQL($SQL);
	//echo "<P>" . $SQL . "</P>";
}

if ($inserted!="_none_") {
	//Test the connection
	$acc = array();
	$acc[$_POST['alias']] = array(
	"user"=>$_POST['ruser'],
	"pass"=>$_POST['rpassword'],
	//"uri"=>"http://localhost:5232/test/",
	"uri"=>"http://" . $_POST['rhost'] . ":" . $_POST['rport']. "/" . $_POST['ruser'] . "/",
	);

	$account = $acc[$_POST['alias']];
	$cal = new CalDAVClient( $account["uri"], $account["user"], $account["pass"], "dummy" );

	$options = $cal->DoOptionsRequest();
	if ($options==false) {
		$bad_connection=true;
		$message="Unable to connect to " . $inserted . ".";
	} else {

			$calendar_string="";
			$calendars = $cal->DoCalendarRequest();
			$arrlength = count($calendars);
			debug("caldav_accounts: $arrlength calendars found in account $inserted");
			if ($arrlength==0) {
				$no_calendars=true;
			} else {
				$calendar_string .= '<table>';
				for($x = 0; $x < $arrlength; $x++) {
					//change from "/username/a5d39a41-1898-ff3c-db9f-111d854564db/" to "/calendarname/a5d39a41-1898-ff3c-db9f-111d854564db/"
					//this value will be exploded into an array after it is posted
					$checkbox_value= str_replace($account['user'],$calendars[$x]['display-name'],$calendars[$x]['url']);
					$calendar_string .= '<tr><td><input type="checkbox" name="cal_checkbox' . $x . '" value="' . $checkbox_value . '" checked></td>';
					$calendar_string .= "<td>NAME: " . $calendars[$x]['display-name'];
					$calendar_string .= "<br>URL: " . $calendars[$x]['url'];
					$calendar_string .= "<br>CTAG: " . $calendars[$x]['ctag'];
					$calendar_string .= "</td></tr>";
					
				}				
				$calendar_string .= '</table>';
			}


			$message = $arrlength . ' calendars found in "' . $inserted . '."';
	}

}
?>

<script>
$(document).ready(function(){
  $(".rm_caldav_acct").html('<img src="images/trash.png" height="24">');
  $(".edit_caldav_acct").html('<img src="images/edit.png" height="16">');
  $(".use_caldav_calendar").html('<img src="images/logo.png" height="16">');
  });

use_caldav_calendar

</script>
<!-- Main content: shift it to the right by 250 pixels when the sidebar is visible -->
<div class="w3-main w3-container w3-padding-16" style="margin-left:250px">

<?php 

//unset these to remove the elements from the page, but include elemLogoHeadline to push the main section below the nav bar. Find a cleaner way of doing this later.
unset ($logo_image);
unset ($headline);
include 'Hydrogen/elemLogoHeadline.php'; 



if (isset($_GET['ID'])) {
	//Case 2 or 3 (delete, edit)
	$account_sql = "SELECT id, alias as Account, ruser as Username, rpassword as Password, rhost as Host, rport as Port from " . DB::$caldav_acct_table . " where owner='" . $_SESSION['username'] . "' ";
	$account_sql = $account_sql . " and id=". (int) $_GET['ID'];
	$result = $dds->setMaxRecs(1);
	$result = $dds->setSQL($account_sql);
	$page_count = $dds->getPageCount();
	if ($page_count>0)  {
		$acctdata = $dds->getNextRow("labeled");
		//set $caldav_id and set new=false if the ID is valid for this user and if $_GET['action'] != delete
		if (isset($_GET['action'])) { 
			if ($_GET['action']="delete") {
				//Case 2
				$delete="DELETE FROM " . DB::$caldav_cal_table . " where owner='" . $_SESSION['username'] . "' and remote_acct_id=". (int) $_GET['ID'];
				$result = $dds->setSQL($delete);
				$delete="DELETE FROM " . DB::$caldav_acct_table . " where owner='" . $_SESSION['username'] . "' and id=". (int) $_GET['ID'];
				$result = $dds->setSQL($delete);				
			}
		} else {
			$new=false;
			$caldav_id = $acctdata['id'];
		}
	}
}

if (isset($_SESSION['username'])) {
	require_once 'Hydrogen/clsDataSource.php';
	require_once 'Hydrogen/clsHTMLTable.php';
	require_once 'Hydrogen/libFilter.php';
	?>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
	
	
	<!-- Show all existing accounts here -->
<?php 	
	$sql = "SELECT id, alias as Account, ruser as Username, rpassword as Password, rhost as Host, rport as Port, id as trash ";
	$sql = $sql . "	from " . DB::$caldav_acct_table . " where owner='" . $_SESSION['username'] . "' ";
	$sql = $sql . " ORDER BY alias";
	//echo "<P>SQL:" . $sql . "</P>";
	echo "<br>" . $message . "<br>";
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
		//$invisible[0] = 1;
		$linkURLs[0] = '?ID=';
		$address_classes[0]='edit_caldav_acct';
		//$keycols[1] = 0;
		$linkURLs[6] = '?action=delete&ID=';
		$address_classes[6]='rm_caldav_acct';
		$keycols[6] = 0;
		$hide_headers[0] = 1;
		$hide_headers[6] = 1;
		echo "<H3>Accounts</h3>";
		$table=new HTMLTable($dds->getFieldNames(),$dds->getFieldTypes());
		$table->defineRows($linkURLs,$keycols,$invisible,$address_classes,$linkTargets,$hide_headers);
		$table->start();
		while ($result_row = $dds->getNextRow()){
			$style='background-color: #ffffff; color: black';
			if ($bad_connection and $result_row[1]==$inserted) $style='background-color: #FF0000; color: black';
			if (!$bad_connection and $result_row[1]==$inserted) $style='background-color: #00FF00; color: black';
			$table->addRow($result_row,$style); 
		}
		$table->finish();
		$sql = "SELECT c.id,c.name,a.alias as 'Account' ,c.uid, c.id as 'Migrate' FROM " . DB::$caldav_cal_table ;
		$sql .= " c inner join " . DB::$caldav_acct_table . " a on a.id=c.remote_acct_id ";
		$sql .= " where c.owner='" . $_SESSION['username'] . "' ";
		$sql .= " ORDER BY remote_acct_id";
		//echo "<P>SQL:" . $sql . "</P>";
		echo "<br>" . $message . "<br>";
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
			$hide_headers=null;
			$address_classes=array();
			$linkURLs=array();
			$linkURLs[4] = 'migrate.php?ID=';
			$address_classes[4]='use_caldav_calendar';

			echo "<H4>Calendars</h4>";
			$table=new HTMLTable($dds->getFieldNames(),$dds->getFieldTypes());
			$table->defineRows($linkURLs,$keycols,$invisible,$address_classes,$linkTargets,$hide_headers);
			$table->start();
			while ($result_row = $dds->getNextRow()){
				$style='background-color: #ffffff; color: black';
				if ($bad_connection and $result_row[1]==$inserted) $style='background-color: #FF0000; color: black';
				if (!$bad_connection and $result_row[1]==$inserted) $style='background-color: #00FF00; color: black';
				$table->addRow($result_row,$style); 
			}
			$table->finish();
		}

	} else {
		echo '<h2>No CalDAV accounts configured.</h2>';	

	}
?>	
	
	<?php 
		if ($new and ($inserted=="_none_" or $bad_connection or $no_calendars)) {
			echo ('<h3>New CalDAV account</h3>'); 
		} else {
			if ($inserted=="_none_") {
				echo ('<h3>Edit CalDAV account</h3>');
				$result = $dds->setSQL($account_sql);
				$acctdata = $dds->getNextRow("labeled");
			}
		}
	?>

	<!-- Account add/edit form -->
	<form class="w3-container<?php if ($inserted!="_none_" and !$no_calendars and !$bad_connection) echo ' w3-hide-large w3-hide-medium w3-hide-small' ?>" action="caldav_accounts.php" method="post">
		<input name="ID" type="hidden" value="<?php echo($caldav_id); ?>">
		<input name="DIRTY_ACCOUNT" type="hidden" value="Y">
		<p>
			<label class="w3-text-red">Account Name (required)</label>
			<!-- The UI max length should be several chars smaller than the database
			to allow for HTML encoding e.g. "'" to "&rsquo;" -->
			<input name="alias" class="w3-input w3-border" type="text" maxlength="30" <?php if(!$new) echo ' value="' . $acctdata['Account'] . '"'; ?> required>
			<label class="w3-text-red">Username</label>
			<input name="ruser" class="w3-input w3-border" type="text" maxlength="30" <?php if(!$new) echo ' value="' . $acctdata['Username'] . '"'; else echo ' value="' . $_SESSION['username'] . '"'; ?> required>
			<label class="w3-text-red">Password (WARNING: this will be stored as plain text. Not recommended for use outside your home LAN.)</label>
			<input name="rpassword" class="w3-input w3-border" type="text" maxlength="30" <?php if(!$new) echo ' value="' . $acctdata['Password'] . '"'; else echo ' value="foo"'; ?> required>
			<label class="w3-text-red">Host</label>
			<input name="rhost" class="w3-input w3-border" type="text" maxlength="30" <?php if(!$new) echo ' value="' . $acctdata['Host'] . '"'; else echo ' value="localhost"'; ?> required>
			<label class="w3-text-red">Port</label>
			<input name="rport" class="w3-input w3-border" type="text" maxlength="5" <?php if(!$new) echo ' value="' . $acctdata['Port'] . '"'; else echo ' value="5232"'; ?> >
		</p>
		 
		<div class="w3-container w3-cell-row">


		</div>
		<input name="btnSubmit" type="Submit" value="Done">

	</form>
	
	<!--  Calendar select form -->
	<form class="w3-container<?php if ($inserted=="_none_" or $bad_connection or $no_calendars) echo ' w3-hide-large w3-hide-medium w3-hide-small' ?>" action="caldav_accounts.php" method="post">
		<input name="SELECT_CALENDARS" type="hidden" value="<?php echo $inserted; ?>">
		<input name="CALDAV_USERNAME" type="hidden" value="<?php  if (isset($_POST['ruser'])) echo $_POST['ruser']; ?>">
		
		<p>
		Select the calendars to use in the "<?php echo $inserted ?>" account. <BR> 
		<?php if (isset($calendar_string)) echo $calendar_string; 			?>
		</p>
		 
		<div class="w3-container w3-cell-row">


		</div>
		<input name="btnSelect" type="Submit" value="Done">

	</form>
	
	
<?php
} else {
	echo '<p>Only registered users may set up a CalDAV account.</p>';	
}	
?>

<!-- END MAIN -->
<p></p>
<p></p>
</div>
<?php include "Hydrogen/elemNavbar.php"; ?>
<?php include "Hydrogen/elemFooter.php"; ?>
</body></html>
