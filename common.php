<?php
//ini_set(opcache.enable,0);

//if (session_status() == PHP_SESSION_NONE) session_start();

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

?>
