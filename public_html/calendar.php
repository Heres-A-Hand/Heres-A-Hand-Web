<?php
/**
 *  Copyright 2011-2013 Here's A Hand Limited
 *
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
 * 
 * @copyright 2011-2013 Here's A Hand Limited
 * @license Apache License, Version 2.0
**/

require '../src/global.php';
mustBeLoggedIn();

$group = getCurrentUserSupportGroupOrDie();
define('CALENDAR_FORMAT','j F');
define('CALENDAR_DAYS',34);

// when we first load calendar we start from todays date and show X days, and don't care where month boundary is.
$inStrictMonthMode = false;

$start = new DateTime ('now');
if (isset($_GET['m']) && intval($_GET['m']) && isset($_GET['y']) && intval($_GET['y'])) {
	$start->setDate($_GET['y'], $_GET['m'], 1);	
	$inStrictMonthMode = true;
	// if we click next/prev or pass in month we show that complete month.
}

$year = $start->format("Y");
$monthInt = $start->format("n");
$monthString = $start->format("F");

// We go back so we always start on a monday
while ($start->format('N') != 1) {
	$start->sub(new DateInterval('P1D'));
} 

$end = new DateTime();
$end->setTimestamp($start->getTimestamp());
$end->add(new DateInterval('P'.CALENDAR_DAYS.'D'));

# set up data arrays
$requests = array();
$holidays = array();
$currentUserOnHoliday = array();
for ($i = $start->getTimestamp(); $i <= $end->getTimestamp(); $i=$i+24*60*60) {
	$requests[date(CALENDAR_FORMAT,$i)] = array();
	$holidays[date(CALENDAR_FORMAT,$i)] = array();
	$currentUserOnHoliday[date(CALENDAR_FORMAT,$i)] = false;
}

# get requests
foreach($group->getRequestsVisibleToUserOnCalendar($CURRENT_USER,$start->getTimestamp(),$end->getTimestamp()) as $req) {
	$requests[date(CALENDAR_FORMAT,$req->getCalendarDateInSeconds())][] = $req;
}

# get holidays
foreach($group->getHolidaysVisibleToUserOnCalendar($CURRENT_USER,$start->getTimestamp(),$end->getTimestamp()) as $holiday) {
	for ($i = $holiday->getFromTimeStamp(); $i <= $holiday->getToTimeStamp(); $i=$i+24*60*60) {
		$holidays[date(CALENDAR_FORMAT,$i)][] = $holiday;
		if ($CURRENT_USER->getId() == $holiday->getUserAccountId()) $currentUserOnHoliday[date(CALENDAR_FORMAT,$i)] = true;
	}
}


# render page
$s = getSmarty();
$s->assign('PAGETITLE','Calendar');
$s->assign('helpPage','calendar');
if ($inStrictMonthMode) {
	$s->assign('inStrictMonthMode',true);
	$s->assign('year',$year);
	$s->assign('monthInt',$monthInt);
	$s->assign('monthString',$monthString);
} else {
	$s->assign('inStrictMonthMode',false);
}

$s->assign('requests',$requests);
$s->assign('holidays',$holidays);
$s->assign('currentUserOnHoliday',$currentUserOnHoliday);

if ($monthInt == 12) {
	$s->assign('nextURL','/calendar.php?supportGroup='.$group->getId().'&m=1&y='.($year+1));
} else {
	$s->assign('nextURL','/calendar.php?supportGroup='.$group->getId().'&m='.($monthInt+1).'&y='.$year);
}
if ($monthInt == 1) {
	$s->assign('prevURL','/calendar.php?supportGroup='.$group->getId().'&m=12&y='.($year-1));
} else {
	$s->assign('prevURL','/calendar.php?supportGroup='.$group->getId().'&m='.($monthInt-1).'&y='.$year);
}

$s->display('calendar.htm');
