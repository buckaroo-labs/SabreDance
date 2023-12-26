<?php
$pagetitle="Administration";
include "Hydrogen/pgTemplate.php";
require('settings.php');
$authFailureText='This page requires authentication.';
$authFailureText2='Invalid username/password. This page requires authentication.';

$realm = $settings['Realm'];

$conn=mysqli_connect($settings['DBHost'],$settings['DBUser'],$settings['DBPass'],$settings['DBName']) ;

function isPOSTedClean($postVar){
	//allow only alphanumeric, '_', '-', '.'; '@' for email only; space (only for display name)
	global $hint;
	$retval=false;
	if (isset($_POST[$postVar])) {
		$testString=str_replace(' ','',$_POST[$postVar]);
		$testString=str_replace('@','',$testString);
		//actually we're going to allow these too, just for passwords sake
		$testString=str_replace('!','',$testString);
		$testString=str_replace('#','',$testString);
		$testString=str_replace('$','',$testString);

		if (preg_match('/^[A-Za-z0-9._-]+$/' , $testString)) {
			$retval=true;
		} else {
			$hint.="<br>Illegal character in value '" . $postVar . "'";
		}
		if (strpos(' ',$_POST[$postVar])!==false && $postVar!='davname') {
			$retval=false;
			$hint.="<br>No space allowed in value '" . $postVar . "'";
		}
		if (strpos($_POST[$postVar],'@')!==false && $postVar!='davmail') {
			$retval=false;
			$hint.="<br>No '@' allowed in value '" . $postVar . "'";
		}
		if (strpos($_POST[$postVar],'@')===false && $postVar=='davmail') {
			$retval=false;
			$hint.="<br>No '@' found in value '" . $postVar . "': " . $_POST[$postVar];
		}

	} else {
		$hint.="<BR>Missing value: " . $postVar;
	}
	return $retval;

}
$badPost=false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$hint='';
	//check for required inputs incl whether sanitized
	if (!isPOSTedClean('davname'))  $badPost=true;
	if (!isPOSTedClean('davuser'))  $badPost=true;
	if (!isPOSTedClean('davpass'))  $badPost=true;
	if (!isPOSTedClean('davmail'))  $badPost=true;
	//
	if (!$badPost) {
                $username=$_POST['davuser'] ;
                $dispname=$_POST['davname'] ;
                $email=$_POST['davmail'] ;
                $hash= md5($username . ':' . $settings['Realm'] . ':' . $_POST['davpass']);
                $sql1="INSERT INTO users (username,digesta1) VALUES ('" . $username ."','" . $hash . "');";

                $sql2="INSERT INTO principals (uri,email,displayname) VALUES ('principals/" . $username . "', '" . $email . "','" . $dispname . "')";
                $sql3="INSERT INTO addressbooks (principaluri, displayname, uri, description, synctoken) VALUES ('principals/" . $username ."','default','default','Default address book','1')";
                $conn->query($sql1) or die ('Unexpected error inserting into users table');
                $conn->query($sql2) or die ('Unexpected error inserting into principals table');
                $conn->query($sql3) or die ('Unexpected error inserting into addressbooks table');

	} else {
		die ("<br>Unexpected or missing character input. Usernames and passwords should be alphanumeric with optional dot, dash or underscore characters. Spaces are allowed only for display name." . $hint);
	}
}
?>


    <style>
	html,body,h1,h2,h3,h4,h5,h6 {font-family: "Roboto", sans-serif;}


	table {
  		border-collapse: collapse;
	}

	tr:hover {background-color: #eeEEEE;}

	td, th {
  		border: 1px solid #dddddd;
  		text-align: left;
  		padding: 8px;
	}

    </style>


<!-- Main content: shift it to the right by 250 pixels when the sidebar is visible -->
<div class="w3-main" style="margin-left:250px">

  <div class="w3-row w3-padding-64">
    <div class="w3-twothird w3-container">

        <?php include "Hydrogen/pgLogin.php"; ?>

    </div>
    <div class="w3-third w3-container">

	<ul><li><a target="_blank" href="server.php">server.php</a></li>
	<li><a target="_blank" href="calendarserver.php">calendarserver.php</a></li>
	<li><a target="_blank" href="addressbookserver.php">addressbookserver.php</a></li>';
	<li><a target="_blank" href="reminders.php">reminders.php</a></li>
	</ul>
<?php 
echo '<h2>Database contents</h2>';

		echo '<h3>Registered users:</h3><table id="users" class="sortable"><tr><th>ID</th><th>Name</th></tr>';
		$sql="select id, username from users";
		$result = $conn->query($sql) or die ("Failed to get result for SQL " . $sql .' '. mysqli_error());
		while ($row=$result->fetch_assoc()) {
			$row_out='<tr><td>' . implode('</td><td>',$row) . '</td></tr>';
			echo $row_out . "\n";
		}
		echo "</table>";

		 echo '<h3>Principals :</h3><table id="principals" class="sortable"><tr><th>id</th><th>uri</th><th>email</th><th>display name</th></tr>';
                 $sql="select id, uri, email, displayname from principals";
                $result = $conn->query($sql) or die ("Failed to get result for SQL " . $sql .' '. mysqli_error());
                 while ($row=$result->fetch_assoc()) {
                         $row_out='<tr><td>' . implode('</td><td>',$row) . '</td></tr>';
                        echo $row_out . "\n";
                }
                echo "</table>";
		echo '<h3>Add a user</h3><p>No spaces allowed except in display name. Values should be alphanumeric with optional dot, dash, or underscore characters.</p>';
		include 'userForm.php';

?>
   
    </div>
  </div>

</div>

<?php include "Hydrogen/elemFooter.php"; include "Hydrogen/elemNavbar.php";?>
</body></html>




