<?php
ini_set(opcache.enable,0);

if (session_status() == PHP_SESSION_NONE) session_start();

function decode_scale ($scale_code) {
	switch ($scale_code) {
		case 0:
			$retval = "hours";
			break;
		case 2:
			$retval = "weeks";
			break;
		case 3:
			$retval = "months";
			break;
		case 4:
			$retval = "years";
			break;
		default:
			$retval = "days";
	}
	return $retval;
}

function decode_scale_and_units ($scale_code, $units, $include_1=false) {
	switch ($scale_code) {
		case 0:
			$scale = "hour";
			break;
		case 2:
			$scale = "week";
			break;
		case 3:
			$scale = "month";
			break;
		case 4:
			$scale = "year";
			break;
		default:
			$scale = "day";
	}
	if (is_null($units)) $units=1;
	if ($units ==1) $retval= $scale; else $retval=  "$units $scale" . "s";
	if ($units ==1 and $include_1) $retval="1 " . $retval;
	return $retval;
}

function check_login() {
	global $authFailureMessage;
	global $settings ;
        require('settings.php');
        $authFailureText='This page requires authentication.';
        $authFailureText2='Invalid username/password. This page requires authentication.';
        $realm = $settings['Realm'];
        $conn=mysqli_connect($settings['DBHost'],$settings['DBUser'],$settings['DBPass'],$settings['DBName']) ;
        $sql="select max(digesta1) as a1, count(*) as rowCount from users where username=?";

        if (empty($_SERVER['PHP_AUTH_DIGEST']) ) {
                header('HTTP/1.1 401 Unauthorized');
                header('WWW-Authenticate: Digest realm="'.$realm.
                           '",qop="auth",nonce="'.uniqid().'",opaque="'.md5($realm).'"');

		$authFailureMessage=$authFailureText;
                return false;

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
	if ($userCount==0) {
		$authFailureMessage=$authFailureText2;
		$stmt->close();
		return false;
	}
        $stmt->close();

        //
        // generate the valid response
        // the following line is the value that will match the hash in the database
        //$A1 = md5($data['username'] . ':' . $realm . ':' . $users[$data['username']]);
        $A2 = md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
        $valid_response = md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);

	if ($data['response'] != $valid_response) {

		$authFailureMessage=$authFailureText2;
		return false;
	}
	$_SESSION['username'] = $data['username'];

	return true;


}

function require_login () {
	global $authFailureMessage;
	if (!check_login()) die($authFailureMessage);
}

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

?>
