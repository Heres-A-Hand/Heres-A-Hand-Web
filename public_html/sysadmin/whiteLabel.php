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
sysAdminMustBeLoggedIn();


$whiteLabel = WhiteLabel::findByID($_GET['id']);
if (!$whiteLabel) die("Not found");



if (isset($_POST['action']) &&  $_POST['CSFRToken'] == $_SESSION['CSFRToken']) {
	if ($_POST['action'] == 'addAdminEmail' && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
		$userAccount = UserAccount::findByEmailOrCreate($_POST['email']);
		$whiteLabel->addAdmin($userAccount);
	} else if ($_POST['action'] == 'removeAdmin' && isset($_POST['userID'])) {
		$userAccount = UserAccount::findByID($_POST['userID']);
		if ($userAccount) $whiteLabel->removeAdmin($userAccount);
	}
}


$s = getSmarty();


$s->assign('PAGETITLE','Sys Admin');
$s->assign('whiteLabel',$whiteLabel);

$sgS = new SupportGroupSearch();
$sgS->inWhiteLabel($whiteLabel);
$s->assign('supportGroupSearch',$sgS);


$adminSearch = new UserAccountSearch();
$adminSearch->canAdminWhiteLabel($whiteLabel);
$s->assign('adminSearch',$adminSearch);

$s->display('sysadmin/whiteLabel.htm');


