<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

function isPOSTedClean($postVar){
	//allow only alphanumeric, '_', '-', '.'
	$retval=false;
	if (isset($_POST[$postVar])) {
		if (preg_match('/^[A-Za-z0-9._-]+$/' , $_POST[$postVar])) $retval=true;

	}
	return $retval;

}
function writeSetting($setting) {
	global $file;
	$data='$settings' . "['" . $setting . "']='" . $_POST[strtolower($setting)] . "';\n";
	fwrite($file,$data);

}
$badPost=false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	//check for the flag file
	if (!file_exists("badDBconnection.flag"))  $badPost=true;
	//
	//check for required inputs incl sanitized
	if (!isPOSTedClean('dbname'))  $badPost=true;
	if (!isPOSTedClean('dbuser'))  $badPost=true;
	if (!isPOSTedClean('dbpass'))  $badPost=true;
	if (!isPOSTedClean('dbhost'))  $badPost=true;
	//
	//write new values to settingsDB.php
	if (!$badPost) {
		$file=fopen("settingsDB.php","w");
		fwrite($file,"<?php" . "\n");
		writeSetting("DBName");
		writeSetting("DBHost");
		writeSetting("DBUser");
		writeSetting("DBPass");
		fwrite($file,"?>" );

		fclose($file);


	}
	//
	//delete the flag file
	if (!$badPost) unlink("badDBconnection.flag");
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
    </style>
    </head>
    <body>
    <H1>SabreDance</H1>
    <P>This starter kit is designed to help you implement sabre/dav by giving you step-by-step instructions, executing setup steps, and providing template files. </P>
<p>Your configuration will be examined each time you load this page, and you may be instructed to refresh this page after making some changes.<p>

<?php
ini_set(opcache.enable,0);

$failed=false;
$feedback='';
$instructions='';

function writeabledirectorycheck($dir) {
	global $failed;
	global $instructions;
	global $feedback;
	$cwdstr='<font color="blue">' . getcwd() . '</font>';
	$dirstr='<font color="red">' . $dir . '</font>';
  if (file_exists($dir)) {
        if (is_file($dir)) {
                $failed=true;
		$feedback.='<BR>The file ' . $dirstr . ' in ' . $cwdstr . ' is meant to be a directory.';
		$instructions.='<BR>cd ' . $cwdstr  . '<BR>rm ' . $dir .  '<BR>mkdir ' . $dir . '<BR>chmod a+rwx '. $dir;
		return false;
        } else {
                if (is_writeable($dir  )) {
			return true;
                } else {
                        $failed=true;
			$feedback.='<BR>The directory ' . $dirstr . ' in ' . $cwdstr  . ' is not writeable.';
			$instructions.='<BR>cd ' . $cwdstr  . '<BR>chmod a+rwx '. $dir;
			return false;
                }
        }
  } else {
        $failed=true;
        $feedback.='<BR>The directory ' . $dirstr . ' does not exist in ' . $cwdstr ;
	$instructions.='<BR>cd ' . $cwdstr  . '<BR>mkdir ' . $dir . '<BR>chmod a+rwx '. $dir;
	return false;
  }
}

function BaseURICheck() {
	global $settings;
	global $feedback;
	global $instructions;
	global $failed;
	if (!file_exists('settings.php')) {
		$failed=true;
		$feedback.='<BR>The file <font="red">settings.php</font> was not found. This should not have happened if you copied it and this page from the same source.';
		return false;
	} else {
		include ('settings.php');
		//check that BaseURI is same as cwd and location of this file
		//settings['BaseURI']='/dav/';
		$samePath=true;
		$rootPath=$_SERVER['DOCUMENT_ROOT'] . $settings['BaseURI'] . 'index.php';
		$cwdPath=getcwd() .'/index.php';
		if ($cwdPath!=__FILE__) $samePath=false;
		if ($rootPath!=__FILE__) $samePath=false;
		if (!$samePath) {
			echo '<font color="red">PLEASE CHECK THAT THE BELOW PATHS ARE IDENTICAL OR EQUIVALENT:</font><BR>'		;
			echo '(And check that the BaseURI in <font color="red">settings.php</font> is correct)<BR><ul>';
			echo '<li>' . $cwdPath . '</li>';
			echo '<li>' . $rootPath . '</li>';
			echo '<li>' . __FILE__ . '</li></ul>';
		}

		return true;

	}

}

function reportAndExit() {
	global $feedback;
	global $instructions;
	echo '<h3>Errors:</h3><p id="feedback">' . $feedback . '</p><p>(Assuming this is a Linux/Unix host) Please execute the following commands (if any) on your server and then refresh this page:</p><p id="instructions">' . $instructions . '</p>';
	die ('   </body></html>');
}

function dirChecks() {
	global $feedback;
	global $instructions;
	global $failed;
	echo '<h3>File and directory scan results:</h3><ul>';
	$HTMLOK='<font color="green"> OK</font>';
	$HTMLfail='<font color="red"> FAILED</font>';
	if (writeabledirectorycheck('data')) $status=$HTMLOK; else $status=$HTMLfail;
	echo '<li>data directory check:' . $status . '</li>';
	if (writeabledirectorycheck('public')) $status=$HTMLOK; else $status=$HTMLfail;
	echo '<li>data directory check:' . $status . '</li>';

	/*
	 * Testing code
	 *
	//test non-writeable directory
	if (writeabledirectorycheck('foo')) $status=$HTMLOK; else $status=$HTMLfail;
	echo '<li>foo directory check:' . $status . '</li>';
	//test file in place of directory
	if (writeabledirectorycheck('bar')) $status=$HTMLOK; else $status=$HTMLfail;
	echo '<li>bar directory check:' . $status . '</li>';
	//test non-existent directory or file
	if (writeabledirectorycheck('squonk')) $status=$HTMLOK; else $status=$HTMLfail;
	echo '<li>squonk directory check:' . $status . '</li>';
	 */

	if (!file_exists('vendor/autoload.php')) {
		 $failed=true;
		 $feedback.='<BR>The file <font="red">vendor/autoload.php</font> was not found in ' . $cwdstr. '. This is created when installing sabre/dav with Composer.';
		 $instructions.='<BR>cd ' . $cwdstr . '<BR> composer require sabre/dav ~3.2.0';
	}

	echo '</ul>';
	if ($failed) return false; else return true;

}

if (!BaseURICheck()) reportAndExit();
if (!dirChecks()) reportAndExit();

if (!$failed) {
	include 'settings.php';
	if ($settings['Realm']!="SabreDAV") {
		$feedback.='<BR>(WARNING) The Realm setting in settings.php is not set to &ldquo;SabreDAV&rdquo;. If you made this modification, you had better know what you are doing.';

	}
	echo "<h3>Database check results:</h3>";

	try {
		$mysqli=mysqli_connect($settings['DBHost'],$settings['DBUser'],$settings['DBPass'],$settings['DBName']) ;
		if ($mysqli===false) {
			echo ('No DB connection. Use the form below to make corrections to your settings (which will be written to settingsDB.php). <br>Alphanumeric and dot/dash characters only.');
			$file = fopen("badDBconnection.flag", 'w') or die("Unable to open new file in " . dirname (__FILE__));
			fclose($file);
			include 'DBForm.php';
		        reportAndExit();

		} else {
			echo "Successful DB connection";
			$sqlFiles=glob("vendor/sabre/dav/examples/sql/mysql*.sql");
			//print_r($sqlFiles);
			foreach ($sqlFiles as $location) {
				$commands = file_get_contents($location);   
				try {
				  echo "<BR>Trying setup file: " . str_replace('vendor/sabre/dav/examples/sql/','',$location);
				  $result=$mysqli->multi_query($commands);
				  if ($result===false) {
					  $errString= mysqli_error($mysqli);
					  if (strpos('already exists',$errString)===false) {
						  echo '<br><font color="green">' . mysqli_error($mysqli) . '</font>';
					  } else {
						  echo '<br><font color="red">ERROR: ' . mysqli_error($mysqli) . '</font>';
				  	  }
				  } else {
					  echo '**Success**';
				  }
				} catch(Exception $e) {
	  				echo '<br>Error: ' .$e->getMessage();
				}
			}


		}
	} catch(Exception $e) {
	  echo 'Error: ' .$e->getMessage();
	  reportAndExit();
	}

}
if (!failed) echo '<p id="success">You&apos;re all set! Here are the links to your servers:</p>';
?>
   </body>
</html>
