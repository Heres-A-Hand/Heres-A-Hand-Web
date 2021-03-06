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

if ($group->isAdmin() && $_POST['CSFRToken'] == $_SESSION['CSFRToken']) {
	// We do email first so if someone sends email and phone they get sent an email and not a phone. They can add phone later themsleves.
	if (isset($_POST['addNewEmail']) && trim($_POST['addNewEmail']) && filter_var($_POST['addNewEmail'], FILTER_VALIDATE_EMAIL)) {

		$userAccount = UserAccount::findByEmailOrCreate($_POST['addNewEmail']);
		if ($group->addUser($userAccount, false, true, $CURRENT_USER)) {
			$_SESSION['flashOK'] = "Thanks, they have been added.";
		} else {
			$_SESSION['flashError'] = "That user is already in your group!";
		}
	} else if ($group->isPremium() && isset($_POST['addNewTelephone']) && trim($_POST['addNewTelephone']) && isset($_POST['addNewTelephoneCountry']) && trim($_POST['addNewTelephoneCountry'])) {

		$number = trim($_POST['addNewTelephone']);

		$userAccount = UserAccount::findByTelephoneOrCreate($_POST['addNewTelephoneCountry'],$number);
		if ($group->addUser($userAccount, false, true, $CURRENT_USER)) {
			$_SESSION['flashOK'] = "Thanks, they have been added.";
		} else {
			$_SESSION['flashError'] = "That user is already in your group!";
		}
	}
}

header("Location: /team.php?supportGroup=".$group->getId());
