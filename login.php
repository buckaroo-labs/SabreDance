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

<ul>
        <li><a target="_blank" href="server.php">server.php</a></li>
<li><a target="_blank" href="calendarserver.php">calendarserver.php</a></li>
<li><a target="_blank" href="addressbookserver.php">addressbookserver.php</a></li>
<li><a target="_blank" href="reminders.php">reminders.php</a></li>
</ul>

   </body>
</html>
