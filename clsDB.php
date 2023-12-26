<?php
class DB {
		public static $reminder_table="reminders";
		public static $caldav_acct_table="principals";
		public static $caldav_cal_table="calendarinstances";

		public static function calDAV_mapped_columns() {
			$columns=array();
			$columns[0]['col_name']="uid";
			$columns[1]['col_name']="summary";
			$columns[2]['col_name']="location";
			$columns[3]['col_name']="url";
			$columns[4]['col_name']="description";
			$columns[5]['col_name']="priority";
			$columns[6]['col_name']="created";
			$columns[7]['col_name']="last_modified";
			$columns[8]['col_name']="prodid";
			$columns[9]['col_name']="category";


			$columns[0]['calDAV_name']="UID";
			$columns[1]['calDAV_name']="SUMMARY";
			$columns[2]['calDAV_name']="LOCATION";
			$columns[3]['calDAV_name']="URL";
			$columns[4]['calDAV_name']="DESCRIPTION";
			$columns[5]['calDAV_name']="PRIORITY";
			$columns[6]['calDAV_name']="CREATED";
			$columns[7]['calDAV_name']="LAST-MODIFIED";
			$columns[8]['calDAV_name']="PRODID";
			$columns[9]['calDAV_name']="CATEGORIES";

			return $columns;

		}
}
?>
