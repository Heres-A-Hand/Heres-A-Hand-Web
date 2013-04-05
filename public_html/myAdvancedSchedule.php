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

if (!$CURRENT_USER->getUseAdvancedSchedule()) {
	header("Location: /mySimpleSchedule.php");
}


$s = getSmarty();

$s->assign('PAGETITLE','Schedule');
$s->assign('CANONICALURL',null);
$s->assign('helpPage','mySchedule');

$s->assign('rules',$CURRENT_USER->getScheduleRules());

$s->assign('emails',$CURRENT_USER->getEmails());
$s->assign('telephones',$CURRENT_USER->getTelephones());
$s->assign('twitters',$CURRENT_USER->getTwitters());

$data = array();
foreach(getCurrentUserSupportGroups() as $group) {
	$data[$group->getId()] = $group->getActiveRequestTypes();
}
$s->assign('types',$data);

if (isset($_SESSION['inSignUpWizard']) && $_SESSION['inSignUpWizard']) {
	$s->assign('inSignUpWizard',true);
} else {
	$s->assign('inSignUpWizard',false);
}
$s->display('myAdvancedSchedule.htm');