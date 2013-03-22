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


$emails = isset($_POST['emails']) && is_array($_POST['emails']) ? $_POST['emails'] : array();
$telephones = isset($_POST['telephones']) && is_array($_POST['telephones']) ? $_POST['telephones'] : array();
$twitters = isset($_POST['twitters']) && is_array($_POST['twitters']) ? $_POST['twitters'] : array();
$types = isset($_POST['types']) && is_array($_POST['types']) ? $_POST['types'] : array();
$days = isset($_POST['days']) && is_array($_POST['days']) ? $_POST['days'] : array();
$fromHours = isset($_POST['fromHours']) ? $_POST['fromHours'] : null;
$toHours = isset($_POST['toHours']) ? $_POST['toHours'] : null;

if (isset($_POST['new']) && $_POST['new'] == 'yes') {
	$CURRENT_USER->newScheduleRule($emails, $telephones, $twitters, $fromHours, $toHours, $days, $types);
	header("Location: /mySchedule.php");
	die();
}

$s = getSmarty();

$s->assign('PAGETITLE','Schedule');
$s->assign('CANONICALURL',null);

// These are being passed back so ideally the template can reshow user data, and if there is a mistake a nice error. TODO
$s->assign('emails',$emails);
$s->assign('telephones',$telephones);
$s->assign('twitters',$twitters);
$s->assign('types',$types);
$s->assign('days',$days);
$s->assign('fromHours',$fromHours);
$s->assign('toHours',$toHours);

$data = array();
foreach(getCurrentUserSupportGroups() as $group) {
	$data[$group->getId()] = $group->getActiveRequestTypes();
}
$s->assign('types',$data);

$s->display('newAdvancedScheduleItem.htm');






