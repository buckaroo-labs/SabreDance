<?php
/*
Used as a substitute for caldav-client.php
when this app is built on top of SabreDAV mysql instance;

implements a subset of the same methods
by doing DB operations rather than DAV over HTTP.

All authentication should be handled by the calling script;
it will not be checked here.

*/

require_once 'settingsDB.php';
require_once 'Hydrogen/libDebug.php';

//$realm = $settings['Realm'];
$conn=mysqli_connect($settings['DBHost'],$settings['DBUser'],$settings['DBPass'],$settings['DBName']) ;

//set this to true to send debug output from this PHP file to the debugger above
$debug[basename(__FILE__)]=false;

class CalDAVClient {

  //purely a dummy
  function __construct( $base_url, $user, $pass, $calendar = '' ) {
  }

  //As long as this doesn't return FALSE, the calling procedure will be happy
  function DoOptionsRequest( $relative_url = "" ) {    return array('GET','PUT','DELETE');  }


  /**
  * List the calendars under the URI
  *
  * @param string $relative_url The URL to make the request to, relative to $base_url
  *
  * @return array[] An array of arrays: each containing url, ctag, and display-name for calendar
  */
  function DoCalendarRequest($relative_url = '' ) {
		global $conn;
		$calendars = array();


		//This function has been tested with Radicale server and the following URI for user "test"
		//$cal = new CalDAVClient( "http://localhost:5232/test/", "test", "password", "dummy" );
		//$calendars = $cal->DoCalendarRequest();
		//No other CalDAV server implementations have been tested yet.

		if ( isset($options["PROPFIND"]) ) {
			$this->SetDepth(1);
			$folder_xml = $this->DoXMLRequest("PROPFIND", $xmlC);
			$actual_xml = substr($folder_xml,strpos($folder_xml,"<multistatus"));
			$simpler_xml = str_replace("C:calendar","Ccalendar",str_replace("CS:getctag","CSgetctag",$actual_xml));
			//debug("SIMPLIFIED XML: \n $simpler_xml \n",__FILE__);
			$xMultiStatus = new SimpleXMLElement($simpler_xml);
			$calendar_count=0;
			foreach ($xMultiStatus  as $xResponse) {
				//debug("XML MULTIRESPONSE",__FILE__);
				$xchild_url="";
				foreach ($xResponse as $xChild) {

					$ctag="";
					$dispname="";
					debug("---XML RESPONSE:: NAME:" . $xChild->getName() . " VALUE:" . $xChild->__toString() ,basename(__FILE__));
					if ($xChild->getName()=="href") {
						$xchild_url= $xChild->__toString();
						debug("href set to $xchild_url",basename(__FILE__));
					}
					foreach ($xChild as $xProps){
						//debug("--- ---XML PROPSTAT",basename(__FILE__));
						foreach ($xProps->children() as $xProp)	{
							//debug("--- --- --- XML PROPERTY:: NAME:" . $xProp->getName() . " VALUE:" . $xProp->__toString(),basename(__FILE__) );
							if ($xProp->getName()=="displayname") {
								$dispname = $xProp->__toString();
							}
							if ($xProp->getName()=="CSgetctag") {
								$ctag= str_replace('"','',$xProp->__toString());
							}

						}
					}

					//now we check each child URL to see if it has a ctag (and display name)
					//		if it does, its info is added to the return array
					debug ("caldav-client: Checking ctag at href $xchild_url ",basename(__FILE__));
					if (strlen($ctag)>0) {
						debug ("caldav-client: Adding calendar $calendar_count ($dispname) with ctag $ctag at href $xchild_url",basename(__FILE__));
						$calendars[$calendar_count]['url']=$xchild_url;
						$calendars[$calendar_count]['ctag']=$ctag;
						$calendars[$calendar_count]['display-name']=$dispname;
						$calendar_count = $calendar_count +1;
					}
				}


			}

		}
		return $calendars;
	}


  /**
  * PUT a text/icalendar resource, returning the etag
  *
  * @param string $relative_url The URL to make the request to, relative to $base_url
  * @param string $icalendar The iCalendar resource to send to the server
  * @param string $etag The etag of an existing resource to be overwritten, or '*' for a new resource.
  *
  * @return string The content of the response from the server
  */
  function DoPUTRequest( $relative_url, $icalendar, $etag = null ) {
  	global $conn;
    $this->body = $icalendar;

    $this->requestMethod = "PUT";
    if ( $etag != null ) {
      $this->SetMatch( ($etag != '*'), $etag );
    }
    $this->SetContentType("text/icalendar");
    $headers = $this->DoRequest($relative_url);

    /**
    * DAViCal will always return the real etag on PUT.  Other CalDAV servers may need
    * more work, but we are assuming we are running against DAViCal in this case.
    */
    $etag = preg_replace( '/^.*Etag: "?([^"\r\n]+)"?\r?\n.*/is', '$1', $headers );
    return $etag;
  }


  /**
  * DELETE a text/icalendar resource
  *
  * @param string $relative_url The URL to make the request to, relative to $base_url
  * @param string $etag The etag of an existing resource to be deleted, or '*' for any resource at that URL.
  *
  * @return int The HTTP Result Code for the DELETE
  */
  function DoDELETERequest( $relative_url, $etag = null ) {
  	//this function will be called by clsCalDAV.php; it will be passed
  	// a "[calendar-uid]/[filename].ics" string as relative_url and a double-quoted string as etag.
  	// etag must match to calendarobjects.etag (unquoted); [filename].ics must match calendarobjects.uri
	global $conn;
    return $headers; //"200 OK"
  }

  /**
  * Get the todos The events are returned as an
  * array of event arrays.  Each event array will have a 'href', 'etag' and 'event'
  * part, where the 'href' is relative to the calendar and the event contains the
  * definition of the event in iCalendar format.
  *
  * @param string    $relative_url The URL relative to the base_url specified when the calendar was opened.  Default ''.
  *
  * @return array An array of the relative URLs, etags, and events, returned from DoCalendarQuery() @see DoCalendarQuery()
  */
  function GetAllTodos($relative_url = "", $etags_only=false  ) {
	global $conn;

    return $this->DoCalendarQuery($filter, $relative_url, $etags_only);
  }


  /**
  * Get the calendar entry by HREF
  *
  * @param string    $href         The href from a call to GetEvents or GetTodos etc.
  * @param string    $relative_url The URL relative to the base_url specified when the calendar was opened.  Default ''.
  *
  * @return string The iCalendar of the calendar entry
  */
  function GetEntryByHref( $href, $relative_url = '' ) {
    return $this->DoGETRequest( $relative_url . $href );
	//debug("GetEntryByHref: submitting GET: " . $relative_url . $href,__FILE__);
  }

}
