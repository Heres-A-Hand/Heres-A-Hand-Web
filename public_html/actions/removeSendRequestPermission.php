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
require '../../src/global.php';
mustBeLoggedIn();

$group = getCurrentUserSupportGroupOrDie();
if (!$group->isAdmin()) die("You are not an admin of this group!");

$member = UserAccount::findByID($_POST['member']);
if (!$member) die("No Member");

If ($_POST['CSFRToken'] != $_SESSION['CSFRToken']) die('CSFR');

$groupForMemberInQuestion = SupportGroup::findByIDForUser($group->getId(),$member);
if ($groupForMemberInQuestion->isAdmin()) {
	$_SESSION['flashError'] = "They are a manager and so can always make requests!";	
} else {
	$group->revokeMakeRequests($member);
	$_SESSION['flashOK'] = "They can not make requests any more.";	
}



header("Location: /team.php?supportGroup=".$group->getId());