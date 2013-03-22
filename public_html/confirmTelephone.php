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
checkUserSession();

$phone = UserTelephone::findByID($_SESSION['confirmTelephoneID'] );
if (!$phone) die("not Loaded\n");
if ($phone->isConfirmed()) die("already confirmed\n");

$userAccount = $phone->getUserAccount();

// if logged in as a different user .... problem! Just die for now.
if ($CURRENT_USER && $CURRENT_USER->getId() != $userAccount->getId()) {
	$s = getSmarty();
	$s->assign('PAGETITLE','Problem: ');
	$s->display('confirm.differentAccount.htm');
	die();
}

if ($userAccount->isAccountCreated()) {
	// just verify this and say thanks, login!
	$phone->markConfirmed();
	logIn($userAccount);

	$s = getSmarty();
	$s->assign('PAGETITLE','Thanks!');
	$s->display('confirmTelephone.phoneVerified.htm');
} else {
	$s = getSmarty();

	if (isset($_POST['password1']) && isset($_POST['password2']) && isset($_POST['name'])) {
		
		if (!isset($_POST['tandc']) || $_POST['tandc'] != 'yes') {
			$s->assign('tandcError',true);
		} else {	
			if (strlen($_POST['password1']) < 3) {
				$s->assign('passwordError','Longer password needed');
			} else {
				if ($_POST['password1'] != $_POST['password2']) {
					$s->assign('passwordError','passwords dont match');
				} else {
	
					$userAccount->createAccount($_POST['name'], $_POST['password1']);
					$phone->markConfirmed();
					if ($_POST['email'] && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
						$userAccount->addEmail($_POST['email'], $_POST['email']);
					}
					logIn($userAccount);
	
					$_SESSION['inSignUpWizard'] = true;
					foreach(SupportGroup::findForUser($userAccount) as $group) {
						if ($group->isUserSettingUp($userAccount)) {
							header("Location: /team.php?supportGroup=".$group->getId());
							die();
						}
					}
					
					header("Location: /myCommunicationMethods.php");
					die();
				}
			}
		}

	}
		
	$s->assign('PAGETITLE','Welcome');
	$s->assign('uID', $userAccount->getId());
	$s->assign('userAccount', $userAccount);	
	$s->assign('displayName',  isset($_POST['name']) ? $_POST['name'] : $userAccount->getDisplayName());
	$s->assign('email', isset($_POST['email']) ? $_POST['email'] : '');
	$s->assign('supportGroups',SupportGroup::findForUser($userAccount));
	$s->display('confirmTelephone.createAccount.htm');
}



