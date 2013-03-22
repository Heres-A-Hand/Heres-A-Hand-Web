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
whiteLabelAdminMustBeLoggedIn();
$whiteLabel = getCurrentUserAdminWhiteLabelOrDie();

$sg = SupportGroup::findByIDForWhiteLabel($_GET['id'], $whiteLabel);
if (!$sg) die('Not Found, or you don\'t have access! Please go back.');

$start = mktime(1, 00, 00, 1, 1, 2011);
$end = mktime(1, 00, 00, 1, 1, 2015);

$stats = new SupportGroupStats($sg,$start,$end);

$s = getSmarty();

if (isset($_POST['action']) && $_POST['CSFRToken'] == $_SESSION['CSFRToken']) {
	if ($_POST['action'] == 'delete') {
		$sg->delete($CURRENT_USER);
		$s->assign('flashOK','Group Deleted');
	} else if ($_POST['action'] == 'removeMakeRequests') {
		$user = UserAccount::findByID($_POST['userID']);
		if ($user) {
			$sg->revokeMakeRequests ($user);
			$s->assign('flashOK','User Request Revoked');
		}
	} else if ($_POST['action'] == 'removeAdmin') {
		$user = UserAccount::findByID($_POST['userID']);
		if ($user) {
			$sg->revokeAdmin ($user);
			$s->assign('flashOK','User Admin Revoked');
		}
	} else if ($_POST['action'] == 'allowMakeRequests') {
		$user = UserAccount::findByID($_POST['userID']);
		if ($user) {
			$sg->allowMakeRequests ($user);
			$s->assign('flashOK','User can make requests');
		}
	} else if ($_POST['action'] == 'allowAdmin') {
		$user = UserAccount::findByID($_POST['userID']);
		if ($user) {
			$sg->allowAdmin($user);
			$s->assign('flashOK','User made Admin');
		}
	} else if ($_POST['action'] == 'addEmail' && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
		$user = UserAccount::findByEmailOrCreate($_POST['email']);
		$sg->addUser($user, false, true, $CURRENT_USER);
		$s->assign('flashOK','User Added');
	}
}



$s->assign('PAGETITLE','White Label Admin');
$s->assign('currentUserWhiteLabel',$whiteLabel);
$s->assign('currentUserWhiteLabels',  getCurrentUserAdminWhiteLabels());

$s->assign('supportGroup',$sg);
$s->assign('supportGroupStats',$stats);

$s->display('whitelabeladmin/supportGroup.htm');


