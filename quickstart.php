<?php
require 'vendor/autoload.php';

define('APPLICATION_NAME', 'Calendar API Quickstart');
define('CREDENTIALS_PATH', 'calendar-api-quickstart.json');
define('CLIENT_SECRET_PATH', 'client_secret.json');
define('SCOPES', implode(' ', array(
  	Google_Service_Calendar::CALENDAR,
	Google_Service_Calendar::CALENDAR_READONLY)
));

/** 
 * Returns an authorized API client.
 * 
 * When the creation of the .json is created, it is essential the correct
 * authentication for the correct scope. If the insufficient permission
 * is returned, removed the calendar-api-quickstart.json and create 
 * another one with the correct permission scope.
 * 
 * @return Google_Client the authorized client object
 */
function getClient() {
  $client = new Google_Client();
  $client->setApplicationName(APPLICATION_NAME);
  $client->setScopes(SCOPES);
  $client->setAuthConfigFile(CLIENT_SECRET_PATH);
  $client->setAccessType('offline');

  // Load previously authorized credentials from a file.
  $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
  if (file_exists($credentialsPath)) {
    $accessToken = file_get_contents($credentialsPath);
  } else {
    // Request authorization from the user.
    $authUrl = $client->createAuthUrl();
    printf("Open the following link in your browser:\n%s\n", $authUrl);
    print 'Enter verification code: ';
    $authCode = trim(fgets(STDIN));

    // Exchange authorization code for an access token.
    $accessToken = $client->authenticate($authCode);

    // Store the credentials to disk.
    if(!file_exists(dirname($credentialsPath))) {
      mkdir(dirname($credentialsPath), 0700, true);
    }
    file_put_contents($credentialsPath, $accessToken);
    printf("Credentials saved to %s\n", $credentialsPath);
  }
  $client->setAccessToken($accessToken);

  // Refresh the token if it's expired.
  if ($client->isAccessTokenExpired()) {
    $client->refreshToken($client->getRefreshToken());
    file_put_contents($credentialsPath, $client->getAccessToken());
  }
  return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path) {
  $homeDirectory = getenv('HOME');
  if (empty($homeDirectory)) {
    $homeDirectory = getenv("HOMEDRIVE") . getenv("HOMEPATH");
  }
  return str_replace('~', realpath($homeDirectory), $path);
}

/**
 * validate a date to check that it correctly
 * formatted before it uses it.
 * @param date - the date to be checked.
 * @return true - if the date is valid
 */
function validateDate($date)
{
	$test_date = '03/22/2010';
	$test_arr  = explode('/', $test_date);
	if (count($test_arr) == 3) {
		if (checkdate($test_arr[0], $test_arr[1], $test_arr[2])) {
			// valid date ...
		} else {
			// problem with dates ...
		}
	} else {
		// problem with input ...
	}
	
	return true;
}

/**
 * Check if the specified range of dates are free
 * or not. If one of the dates within the range of
 * dates are not free, the entire block of dates
 * are marked as not free. The date range given
 * is inclusive of the search.
 * @param service the service which the API is 
 * 			called through
 * @param date_start the start date of the range
 * @param date_end the end date of the range
 * @return boolean true, all dates are free
 */
function areDatesFree($service, $date_start, $date_end)
{
	$retVal = false;
	
	/**
	 * set the parameters for the date start and the date
	 * end.
	 */
	$calendarId = 'du2l959rakcfnf8ogu8rf1hcsg@group.calendar.google.com';
	$optParams = array(
			'timeMin' => $date_start,
			'timeMax' => $date_end,
	);
	
	$results = $service->events->listEvents($calendarId, $optParams);
	
	if (count($results->getItems()) == 0) {
		$retVal = true;
	} else {
		$retVal = false;
	}
	
	return $retVal;
}


/**
 * For a specific range of dates, an array of free dates
 * will be returned. The search range is inclusive.
 * @param service the service which the API is 
 * 			called through
 * @param date_start the start date of the range
 * @param date_end the end date of the range
 * @return array of dates - an array of free dates. If there isn't
 * 			free dates, an empty event is returned.
 */
function getDatesFree($service, $date_start, $date_end)
{
	$retVal = array();
	$index = 0;
	
	/**
	 * set the parameters for the date start and the date
	 * end.
	 */
	$calendarId = 'du2l959rakcfnf8ogu8rf1hcsg@group.calendar.google.com';
	$optParams = array(
			'timeMin' => $date_start,
			'timeMax' => $date_end,
	);
	
	// get the list of dates
	$results = $service->events->listEvents($calendarId, $optParams);
	
	/**
	 * extract the free dates and put them into an array for the 
	 * returning value.
	 */ 
	
	if (count($results->getItems()) != 0) {
		foreach ($results->getItems() as $event) {
			$retVal[$index] = $event;
			$index++;
		}
	} 
	
	return $retVal;
}

/**
 * Book a specific range of dates. A start date and end date
 * combination is provided. If the dates free, the dates
 * are marked as booked. The range is inclusive.
 * @param service the service which the API is 
 * 			called through
 * @param summary - the title of the event
 * @param date_start the start date of the event
 * @param date_end the end date of the event
 * @return nothing
 */
function bookDates($service, $summary, $date_start, $date_end)
{
	$calendarId = 'du2l959rakcfnf8ogu8rf1hcsg@group.calendar.google.com';
	date_default_timezone_set('Europe/Berlin');
	
	$event = new Google_Service_Calendar_Event();
	$event->setSummary($summary);
	// $event->setLocation('At the orchard');
	$start = new Google_Service_Calendar_EventDateTime();
	$start->setDateTime($date_start);
	$event->setStart($start);
	$end = new Google_Service_Calendar_EventDateTime();
	$end->setDateTime($date_end);
	$event->setEnd($end);
	
	$createdEvent = $service->events->insert($calendarId, $event);
}

/**
 * free a specific range of dates. A start date and end date
 * combination is provided. If the dates free, the dates
 * are marked as free. The range is inclusive.
 * @param date_start the start date of the range
 * @param date_end the end date of the range
 * @return boolean true - all dates are booked.
 */
function freeDates($service, $date_start, $date_end)
{

}




function dumpEvents($client, $service, $number_of_events)
{
	// Print the next 10 events on the user's calendar.
	$calendarId = 'du2l959rakcfnf8ogu8rf1hcsg@group.calendar.google.com';
	date_default_timezone_set('Europe/Berlin');
	$optParams = array(
			'maxResults' => $number_of_events,
			'orderBy' => 'startTime',
			'singleEvents' => TRUE,
			'timeMin' => date(DateTime::ATOM),
	);
	
 	var_dump($optParams["timeMin"]);
 	var_dump(DateTime::ATOM);
	
	$results = $service->events->listEvents($calendarId, $optParams);
	
	if (count($results->getItems()) == 0) {
		print "No upcoming events found.\n";
	} else {
		print "Upcoming events:\n";
	
		foreach ($results->getItems() as $event) {
			$start = $event->start->dateTime;
			if (empty($start)) {
				$start = $event->start->date;
			}
			$end = $event->end->dateTime;
			if (empty($end)) {
				$end = $event->end->date;
			}
			printf("%s (%s to %s)\n", $event->getSummary(), $start, $end);
		}
	}
}

function prototype()
{
	date_default_timezone_set('Europe/Berlin');
	$timeMin = date(DateTime::ATOM);
	$mytime = date(DateTime::ATOM, mktime(0,0,0,1,31,2015));
	
	var_dump($timeMin);
	var_dump(DateTime::ATOM);
	var_dump($mytime);
	
}

function test_getDatesFree($service, $timeMin, $timeMax)
{
	$events = getDatesFree($service, $timeMin, $timeMax);
	
	if (count($events) != 0)
	{
		foreach ($events as $event) {
			$start = $event->start->dateTime;
			if (empty($start)) {
				$start = $event->start->date;
			}
			$end = $event->end->dateTime;
			if (empty($end)) {
				$end = $event->end->date;
			}
			printf("%s (%s to %s)\n", $event->getSummary(), $start, $end);
		}
	}
	else
	{
		printf("No Free dates\n");
	}
}

function test_array()
{
	$myarray = array("fatdog","fatcat");
	var_dump($myarray);
	
	$myarray[3] = "skinnymouse";
	var_dump($myarray);
	
	return $myarray;
}

// Get the API client and construct the service object.

$client = getClient();
$service = new Google_Service_Calendar($client);
/*
$number_of_events = 10;
dumpEvents($client, $service, $number_of_events);
*/

/*
date_default_timezone_set('Europe/Berlin');
$timeMin = date(DateTime::ATOM, mktime(0,0,0,5,15,2015));
$timeMax = date(DateTime::ATOM, mktime(0,0,0,5,16,2015));

$flag = areDatesFree($service, $timeMin, $timeMax);

if ($flag == true)
{
	print ("dates are free\n");
}
else
{
	print ("dates are busy\n");
}
*/

date_default_timezone_set('Europe/Berlin');
$timeMin = date(DateTime::ATOM, mktime(0,0,0,5,22,2015));
$timeMax = date(DateTime::ATOM, mktime(0,0,0,5,23,2015));

bookDates($service, "going to ride my horse", $timeMin, $timeMax);

test_getDatesFree($service, $timeMin, $timeMax);




