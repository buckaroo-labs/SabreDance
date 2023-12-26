<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require('settings.php');
$authFailureText='This page requires authentication.';
$authFailureText2='Invalid username/password. This page requires authentication.';

$realm = $settings['Realm'];
//https://www.php.net/manual/en/features.http-auth.php
//
//The info in this line will be replaced by a DB check
//$users = array('admin' => 'mypass', 'guest' => 'goaway');
$conn=mysqli_connect($settings['DBHost'],$settings['DBUser'],$settings['DBPass'],$settings['DBName']) ;
$sql="select max(digesta1) as a1, count(*) as rowCount from users where username=?";

if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Digest realm="'.$realm.
           '",qop="auth",nonce="'.uniqid().'",opaque="'.md5($realm).'"');

    die($authFailureText);
}


// analyze the PHP_AUTH_DIGEST variable
if (!($data = http_digest_parse($_SERVER['PHP_AUTH_DIGEST']))) die($authFailureText2);

//Also die if username is invalid
$stmt=$conn->prepare($sql);
if ( false===$stmt ) die('prepare() failed: ' . htmlspecialchars($conn->error));
$rc = $stmt->bind_param("s",$data['username']);
if ( false===$rc ) die('bind_param() failed: ' . htmlspecialchars($stmt->error));

$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_array(MYSQLI_ASSOC);

$A1=$row['a1'];
$userCount=$row['rowCount'];
if ($userCount==0) die ($authFailureText2);
$stmt->close();

//
// generate the valid response
// the following line is the value that will match the hash in the database
//$A1 = md5($data['username'] . ':' . $realm . ':' . $users[$data['username']]);
$A2 = md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
$valid_response = md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);

if ($data['response'] != $valid_response) die($authFailureText2);

// ok, valid username & password
echo 'You are logged in as: ' . $data['username'];
//echo '<BR>' . $_SERVER['PHP_AUTH_DIGEST'];


// function to parse the http auth header
function http_digest_parse($txt)
{
    // protect against missing data
    $needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
    $data = array();
    $keys = implode('|', array_keys($needed_parts));

    preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);

    foreach ($matches as $m) {
        $data[$m[1]] = $m[3] ? $m[3] : $m[4];
        unset($needed_parts[$m[1]]);
    }

    return $needed_parts ? false : $data;
}

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
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="format-detection" content="telephone=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>SabreDance</title>
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
    </head>
    <body>
    <H1>SabreDance: Admin page</H1>

<?php
ini_set(opcache.enable,0);

	include 'settings.php';

		$mysqli=mysqli_connect($settings['DBHost'],$settings['DBUser'],$settings['DBPass'],$settings['DBName']) ;
/*

	$result = $mysqli->query($sql) or die ("Failed to get result for SQL " . $sql .' '. mysqli_error());
	while ($row=$result->fetch_assoc()) {
	$row_out='<tr><td>' . implode('</td><td>',$row) . '</td></tr>';
	echo $row_out . "\n";
	}

*/

	echo '<ul><li><a target="_blank" href="server.php">server.php</a></li>
	<li><a target="_blank" href="calendarserver.php">calendarserver.php</a></li>
	<li><a target="_blank" href="addressbookserver.php">addressbookserver.php</a></li>';
	if (file_exists("Hydrogen")) echo('<li><a target="_blank" href="reminders.php">reminders.php</a></li>' ;
	echo '</ul>';

echo '<h2>Database contents</h2>';

		echo '<h3>Registered users:</h3><table id="users" class="sortable"><tr><th>ID</th><th>Name</th></tr>';
		$sql="select id, username from users";
		$result = $mysqli->query($sql) or die ("Failed to get result for SQL " . $sql .' '. mysqli_error());
		while ($row=$result->fetch_assoc()) {
			$row_out='<tr><td>' . implode('</td><td>',$row) . '</td></tr>';
			echo $row_out . "\n";
		}
		echo "</table>";

		 echo '<h3>Principals :</h3><table id="principals" class="sortable"><tr><th>id</th><th>uri</th><th>email</th><th>display name</th></tr>';
                 $sql="select id, uri, email, displayname from principals";
                $result = $mysqli->query($sql) or die ("Failed to get result for SQL " . $sql .' '. mysqli_error());
                 while ($row=$result->fetch_assoc()) {
                         $row_out='<tr><td>' . implode('</td><td>',$row) . '</td></tr>';
                        echo $row_out . "\n";
                }
                echo "</table>";
		echo '<h3>Add a user</h3><p>No spaces allowed except in display name. Values should be alphanumeric with optional dot, dash, or underscore characters.</p>';
		include 'userForm.php';

?>
   </body>
</html>
